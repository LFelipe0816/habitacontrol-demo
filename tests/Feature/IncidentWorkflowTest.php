<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Street;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        Storage::fake('public');
    }

    private function as(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function payload(array $over = []): array
    {
        return $over + ['title' => 'Fuga en cocina', 'type' => 'Plomería', 'priority' => 'Alta', 'location_type' => 'common_area', 'reference' => 'Cisterna'];
    }

    private function open(User $user, array $over = [], array $files = []): Incident
    {
        $this->actingAs($user)->post('/incidencias', $this->payload($over) + ['evidence' => $files])->assertSessionHasNoErrors();

        return Incident::latest('id')->firstOrFail();
    }

    public function test_a_resident_reports_on_their_apartment_with_photo_and_video(): void
    {
        $resident = $this->as('residente');

        $incident = $this->open($resident, ['location_type' => 'apartment', 'unit_id' => $resident->unit_id, 'reference' => 'Baño'], [
            UploadedFile::fake()->image('fuga.jpg'), UploadedFile::fake()->create('fuga.mp4', 500, 'video/mp4'),
        ]);

        $this->assertSame($resident->community_id, $incident->community_id);
        $this->assertSame($resident->unit_id, $incident->unit_id);
        $this->assertStringContainsString('Baño', $incident->location);
        $this->assertStringStartsWith('Apto 204', $incident->location);
        $this->assertSame(['image/jpeg', 'video/mp4'], $incident->attachments->pluck('mime')->sort()->values()->all());
        $this->assertStringContainsString('Reporte creado por', $incident->entries->first()->text);
        $this->assertDatabaseHas('activity_logs', ['subject_id' => $incident->id, 'action' => 'Recibida']);
    }

    public function test_a_resident_cannot_report_on_someone_elses_apartment(): void
    {
        $other = User::where('role', 'propietario')->firstOrFail();

        $this->actingAs($this->as('residente'))->post('/incidencias', $this->payload(['location_type' => 'apartment', 'unit_id' => $other->unit_id]))
            ->assertSessionHasErrors('unit_id');
    }

    public function test_location_ids_must_belong_to_the_reports_community(): void
    {
        $foreign = Street::where('community_id', '!=', $this->as('admin')->community_id)->firstOrFail();

        $this->actingAs($this->as('admin'))->post('/incidencias', $this->payload(['location_type' => 'street', 'street_id' => $foreign->id]))
            ->assertSessionHasErrors('street_id');
        $this->actingAs($this->as('admin'))->post('/incidencias', $this->payload(['reference' => null]))->assertSessionHasErrors('reference');
    }

    public function test_only_photos_and_videos_are_accepted_as_evidence(): void
    {
        $this->actingAs($this->as('residente'))->post('/incidencias', $this->payload(['evidence' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]]))
            ->assertSessionHasErrors('evidence.0');
    }

    public function test_status_changes_track_first_response_and_closing(): void
    {
        $admin = $this->as('admin');
        $incident = $this->open($admin);
        $this->assertNull($incident->first_response_at);

        $this->actingAs($admin)->patch("/incidencias/{$incident->code}/estado", ['status' => 'En gestión']);
        $first = $incident->fresh()->first_response_at;
        $this->assertNotNull($first);

        $this->actingAs($admin)->patch("/incidencias/{$incident->code}/estado", ['status' => 'Resuelta']);
        $this->assertNotNull($incident->fresh()->closed_at);
        $this->assertEquals($first, $incident->fresh()->first_response_at);

        $this->actingAs($admin)->patch("/incidencias/{$incident->code}/estado", ['status' => 'En gestión']);
        $this->assertNull($incident->fresh()->closed_at);
        $this->assertGreaterThanOrEqual(3, $incident->entries()->where('text', 'like', 'Estado:%')->count());
    }

    public function test_assigning_a_role_moves_a_new_case_to_assigned(): void
    {
        $incident = $this->open($this->as('admin'));

        $this->actingAs($this->as('admin'))->patch("/incidencias/{$incident->code}/asignar", ['assignee_role' => 'mantenimiento'])->assertSessionHasNoErrors();

        $this->assertSame('mantenimiento', $incident->fresh()->assignee_role);
        $this->assertSame('Asignada', $incident->fresh()->status);
    }

    public function test_maintenance_only_sees_and_manages_cases_assigned_to_them(): void
    {
        $tech = $this->as('mantenimiento');
        $incident = $this->open($this->as('admin'));

        $this->actingAs($tech)->get("/incidencias/{$incident->code}")->assertForbidden();

        $this->actingAs($this->as('admin'))->patch("/incidencias/{$incident->code}/asignar", ['assignee_id' => $tech->id]);
        $this->actingAs($tech)->get("/incidencias/{$incident->code}")->assertOk();
        $this->actingAs($tech)->patch("/incidencias/{$incident->code}/estado", ['status' => 'En gestión'])->assertSessionHasNoErrors();
        $this->actingAs($tech)->patch("/incidencias/{$incident->code}/asignar", ['assignee_id' => $tech->id])->assertForbidden();
    }

    public function test_staff_uploads_solution_evidence_and_the_board_counts_it(): void
    {
        $admin = $this->as('admin');
        $incident = $this->open($admin);

        $this->actingAs($admin)->post("/incidencias/{$incident->code}/evidencias", ['collection' => 'solution', 'files' => [UploadedFile::fake()->image('listo.png')]])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch("/incidencias/{$incident->code}/estado", ['status' => 'Resuelta']);

        $this->assertSame(1, $incident->attachments()->where('collection', 'solution')->count());
        $this->actingAs($admin)->get('/incidencias?view=todas')->assertInertia(fn ($p) => $p->where('stats.closed_documented', fn ($n) => $n >= 1));
    }

    public function test_the_reporter_confirms_or_rejects_the_solution(): void
    {
        $resident = $this->as('residente');
        $incident = $this->open($resident);
        $this->actingAs($resident)->post("/incidencias/{$incident->code}/confirmacion", ['resolved' => true])->assertForbidden(); // aún no está resuelto

        $this->actingAs($this->as('admin'))->patch("/incidencias/{$incident->code}/estado", ['status' => 'Resuelta']);
        $this->actingAs($resident)->post("/incidencias/{$incident->code}/confirmacion", ['resolved' => false, 'feedback' => 'Sigue goteando']);
        $this->assertSame('En gestión', $incident->fresh()->status);
        $this->assertSame('Sigue goteando', $incident->fresh()->resident_feedback);

        $this->actingAs($this->as('admin'))->patch("/incidencias/{$incident->code}/estado", ['status' => 'Resuelta']);
        $this->actingAs($resident)->post("/incidencias/{$incident->code}/confirmacion", ['resolved' => true]);
        $this->assertSame('Cerrada', $incident->fresh()->status);
        $this->assertNotNull($incident->fresh()->resident_confirmed_at);
    }

    public function test_the_reporter_never_sees_internal_notes_or_admin_notes(): void
    {
        $resident = $this->as('residente');
        $incident = $this->open($resident);
        $admin = $this->as('admin');
        $this->actingAs($admin)->patch("/incidencias/{$incident->code}/detalle", ['admin_notes' => 'Vecino conflictivo']);
        $this->actingAs($admin)->post("/incidencias/{$incident->code}/comentarios", ['kind' => 'int', 'text' => 'Nota privada']);
        $this->actingAs($admin)->post("/incidencias/{$incident->code}/comentarios", ['kind' => 'res', 'text' => 'Vamos en camino']);

        $this->actingAs($resident)->get("/incidencias/{$incident->code}")->assertInertia(fn ($p) => $p
            ->where('incident.admin_notes', null)->where('can.internal', false)
            ->where('incident.timeline', fn ($t) => collect($t)->pluck('text')->contains('Vamos en camino') && ! collect($t)->pluck('text')->contains('Nota privada')));
        $this->actingAs($resident)->post("/incidencias/{$incident->code}/comentarios", ['kind' => 'int', 'text' => 'x'])->assertForbidden();
        $this->actingAs($admin)->get("/incidencias/{$incident->code}")->assertInertia(fn ($p) => $p->where('incident.admin_notes', 'Vecino conflictivo'));
    }

    public function test_residents_cannot_change_status_or_assign(): void
    {
        $resident = $this->as('residente');
        $incident = $this->open($resident);

        $this->actingAs($resident)->patch("/incidencias/{$incident->code}/estado", ['status' => 'Cerrada'])->assertForbidden();
        $this->actingAs($resident)->post("/incidencias/{$incident->code}/evidencias", ['collection' => 'solution', 'files' => [UploadedFile::fake()->image('a.png')]])->assertForbidden();
    }

    public function test_the_create_form_offers_a_residents_own_units_only(): void
    {
        $resident = $this->as('residente');

        $this->actingAs($resident)->get('/incidencias/crear')->assertInertia(fn ($p) => $p->component('Incidents/Create')
            ->has('units', 1)->where('units.0.id', $resident->unit_id)->where('communities', []));
        $this->actingAs($this->as('admin'))->get('/incidencias/crear')->assertInertia(fn ($p) => $p->has('communities', 3)->has('blocks')->has('streets'));
    }
}

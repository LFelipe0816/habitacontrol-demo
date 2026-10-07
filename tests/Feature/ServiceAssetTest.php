<?php

namespace Tests\Feature;

use App\Models\ServiceAsset;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function as(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function asset(string $status = 'alerta'): ServiceAsset
    {
        return ServiceAsset::where('status', $status)->firstOrFail();
    }

    public function test_admin_maintenance_and_board_see_the_services_board(): void
    {
        foreach (['admin', 'mantenimiento', 'junta'] as $role) {
            $this->actingAs($this->as($role))->get('/servicios')->assertOk()
                ->assertInertia(fn ($p) => $p->component('Services/Index')->where('stats.total', 6)->where('stats.attention', 3)->has('assets', 6)->has('upcoming'));
        }
    }

    public function test_residents_and_security_cannot_open_services(): void
    {
        $this->actingAs($this->as('residente'))->get('/servicios')->assertForbidden();
        $this->actingAs($this->as('seguridad'))->get('/servicios')->assertForbidden();
    }

    public function test_the_alert_asset_is_selected_by_default_and_overdue_dates_are_flagged(): void
    {
        $this->actingAs($this->as('admin'))->get('/servicios')->assertInertia(fn ($p) => $p
            ->where('selected', $this->asset('alerta')->id)
            ->where('assets', fn ($a) => collect($a)->firstWhere('status', 'alerta')['overdue'] === true));
    }

    public function test_maintenance_staff_can_only_change_operational_fields(): void
    {
        $asset = $this->asset('operativo');

        $this->actingAs($this->as('mantenimiento'))->patch("/servicios/{$asset->id}", ['status' => 'alerta', 'reading' => 30, 'name' => 'Renombrado', 'provider' => 'Otro'])
            ->assertSessionHasNoErrors();

        $fresh = $asset->fresh();
        $this->assertSame('alerta', $fresh->status);
        $this->assertSame(30, $fresh->reading);
        $this->assertSame($asset->name, $fresh->name);
        $this->assertSame($asset->provider, $fresh->provider);
        $this->assertDatabaseHas('activity_logs', ['subject_id' => $asset->id, 'subject_type' => ServiceAsset::class, 'action' => 'Activo actualizado']);
    }

    public function test_the_board_is_read_only(): void
    {
        $asset = $this->asset();

        $this->actingAs($this->as('junta'))->patch("/servicios/{$asset->id}", ['status' => 'operativo'])->assertForbidden();
        $this->actingAs($this->as('junta'))->post("/servicios/{$asset->id}/mantenimientos", ['scheduled_for' => today()->addDay()->toDateString(), 'type' => 'preventivo'])->assertForbidden();
    }

    public function test_readings_are_validated(): void
    {
        $this->actingAs($this->as('admin'))->patch("/servicios/{$this->asset()->id}", ['health' => 140, 'status' => 'roto'])
            ->assertSessionHasErrors(['health', 'status']);
    }

    public function test_scheduling_and_completing_a_maintenance_returns_the_asset_to_operation(): void
    {
        $tech = $this->as('mantenimiento');
        $asset = $this->asset('mantenimiento');
        $pending = $asset->maintenances()->whereNull('performed_on')->firstOrFail();

        $this->actingAs($tech)->patch("/servicios/mantenimientos/{$pending->id}/completar", ['performed_on' => today()->toDateString(), 'health' => 95, 'notes' => 'Bomba limpia'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($pending->fresh()->performed_on);
        $this->assertSame($tech->id, $pending->fresh()->performed_by);
        $this->assertSame('operativo', $asset->fresh()->status);
        $this->assertSame(95, $asset->fresh()->health);

        $this->actingAs($tech)->patch("/servicios/mantenimientos/{$pending->id}/completar", ['performed_on' => today()->toDateString()])->assertStatus(422); // ya completado
    }

    public function test_a_maintenance_cannot_be_scheduled_in_the_past_or_done_in_the_future(): void
    {
        $admin = $this->as('admin');
        $asset = $this->asset();
        $pending = $asset->maintenances()->whereNull('performed_on')->firstOrFail();

        $this->actingAs($admin)->post("/servicios/{$asset->id}/mantenimientos", ['scheduled_for' => today()->subDay()->toDateString(), 'type' => 'preventivo'])->assertSessionHasErrors('scheduled_for');
        $this->actingAs($admin)->patch("/servicios/mantenimientos/{$pending->id}/completar", ['performed_on' => today()->addDay()->toDateString()])->assertSessionHasErrors('performed_on');
        $this->actingAs($admin)->post("/servicios/{$asset->id}/mantenimientos", ['scheduled_for' => today()->addDays(3)->toDateString(), 'type' => 'correctivo'])->assertSessionHasNoErrors();
        $this->assertSame(2, $asset->maintenances()->whereNull('performed_on')->count());
    }

    public function test_only_admin_registers_new_assets_and_they_are_scoped_to_the_community(): void
    {
        $payload = ['category' => 'Energía', 'name' => 'Inversor solar', 'routine' => ['Limpiar paneles']];

        $this->actingAs($this->as('mantenimiento'))->post('/servicios', $payload)->assertForbidden();
        $this->actingAs($this->as('admin'))->post('/servicios', $payload)->assertRedirect();

        $this->assertSame($this->as('admin')->community_id, ServiceAsset::where('name', 'Inversor solar')->firstOrFail()->community_id);
    }

    public function test_the_incident_form_accepts_a_breakdown_prefill(): void
    {
        $this->actingAs($this->as('admin'))->get('/incidencias/crear?'.http_build_query(['asunto' => 'Avería: Pozo B', 'tipo' => 'Mantenimiento', 'categoria' => 'Inventado', 'referencia' => 'Pozo B']))
            ->assertInertia(fn ($p) => $p->where('prefill.title', 'Avería: Pozo B')->where('prefill.type', 'Mantenimiento')->where('prefill.scope', null)->where('prefill.reference', 'Pozo B'));
    }
}

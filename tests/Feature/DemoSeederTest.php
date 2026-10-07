<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Incident;
use App\Models\Poll;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_loads_the_three_communities_with_their_structure(): void
    {
        $jardines = Community::where('name', 'Residencial Los Jardines')->firstOrFail();

        $this->assertSame(3, Community::count());
        $this->assertSame('activo', $jardines->status);
        $this->assertSame('integral', $jardines->plan->key);
        $this->assertNotNull($jardines->company);
        $this->assertGreaterThan(0, $jardines->blocks()->count());
        $this->assertGreaterThan(0, $jardines->buildings()->count());
        $this->assertGreaterThan(0, $jardines->streets()->count());
        $this->assertSame(97, $jardines->units()->count());
    }

    public function test_every_seeded_role_is_a_known_role(): void
    {
        $this->assertEqualsCanonicalizing(
            ['superadmin', 'admin', 'propietario', 'residente', 'seguridad', 'mantenimiento', 'junta'],
            User::distinct()->pluck('role')->all(),
        );
    }

    public function test_rented_unit_links_owner_tenant_and_lease(): void
    {
        $unit = Unit::where('occupancy', 'alquilado')->firstOrFail();

        $this->assertEqualsCanonicalizing(['propietario', 'inquilino'], $unit->people->pluck('pivot.relation')->all());
        $this->assertSame($unit->people->firstWhere('pivot.relation', 'inquilino')->id, $unit->leases->first()->tenant_id);
    }

    public function test_incidents_keep_location_scope_and_evidence(): void
    {
        $street = Incident::where('location_type', 'street')->with('street', 'attachments')->firstOrFail();

        $this->assertNotNull($street->street);
        $this->assertNotNull($street->scope);
        $this->assertStringStartsWith('INC-', $street->code);
        $this->assertTrue(Incident::has('attachments')->exists());
    }

    public function test_poll_votes_are_counted_from_rows_and_limited_to_one_per_user(): void
    {
        $poll = Poll::with('options')->firstOrFail();
        $voter = $poll->votes()->firstOrFail();

        $this->assertSame($poll->votes()->count(), $poll->options->sum(fn ($o) => $o->votes()->count()));
        $this->expectException(QueryException::class);
        $poll->votes()->create(['user_id' => $voter->user_id, 'poll_option_id' => $voter->poll_option_id]);
    }

    public function test_unit_code_is_unique_per_community_not_globally(): void
    {
        [$a, $b] = Community::take(2)->get();
        $a->units()->create(['code' => 'ZZ-1']);
        $b->units()->create(['code' => 'ZZ-1']);

        $this->assertSame(2, Unit::where('code', 'ZZ-1')->count());
        $this->expectException(QueryException::class);
        $a->units()->create(['code' => 'ZZ-1']);
    }
}

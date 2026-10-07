<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Company;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioTest extends TestCase
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

    public function test_staff_sees_the_portfolio_of_their_company(): void
    {
        $this->actingAs($this->as('admin'))->get('/residenciales')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Communities/Index')->has('communities', 3)->where('totals.communities', 3));
    }

    public function test_staff_never_sees_communities_of_another_company(): void
    {
        $foreign = Community::create(['name' => 'Ajeno', 'company_id' => Company::create(['name' => 'Otra'])->id]);

        $this->actingAs($this->as('admin'))->get('/residenciales')->assertInertia(fn ($page) => $page->has('communities', 3));
        $this->actingAs($this->as('admin'))->get("/residenciales/{$foreign->id}")->assertForbidden();
        $this->actingAs($this->as('superadmin'))->get('/residenciales')->assertInertia(fn ($page) => $page->has('communities', 4));
    }

    public function test_residents_and_owners_cannot_open_the_portfolio(): void
    {
        $this->actingAs($this->as('residente'))->get('/residenciales')->assertForbidden();
        $this->actingAs($this->as('propietario'))->get('/residenciales')->assertForbidden();
    }

    public function test_structure_defaults_to_first_block_and_a_building_with_units(): void
    {
        $jardines = Community::where('name', 'Residencial Los Jardines')->firstOrFail();

        $this->actingAs($this->as('admin'))->get("/residenciales/{$jardines->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Communities/Show')
                ->where('residential.units_count', 1008)
                ->where('residential.loaded_units', 97)
                ->where('selected.block', $jardines->blocks()->first()->id)
                ->has('blocks', 4)->has('streets', 4)
                ->where('units.0.number', fn ($n) => is_string($n)));
    }

    public function test_selection_comes_from_the_query_string(): void
    {
        $jardines = Community::where('name', 'Residencial Los Jardines')->firstOrFail();
        $block = $jardines->blocks()->where('code', 'B')->firstOrFail();

        $this->actingAs($this->as('admin'))->get("/residenciales/{$jardines->id}?manzana={$block->id}")
            ->assertInertia(fn ($page) => $page->where('selected.block', $block->id)->has('buildings'));
    }

    public function test_unit_sheet_hides_internal_notes_from_residents(): void
    {
        $unit = Unit::where('occupancy', 'alquilado')->firstOrFail();
        $owner = $unit->people()->wherePivot('relation', 'propietario')->firstOrFail();

        $this->actingAs($this->as('admin'))->get("/unidades/{$unit->id}")
            ->assertOk()->assertInertia(fn ($page) => $page->component('Units/Show')->where('can.internal', true)->where('unit.notes', fn ($n) => filled($n))->has('people', 2)->has('lease'));

        $this->actingAs($owner)->get("/unidades/{$unit->id}")
            ->assertOk()->assertInertia(fn ($page) => $page->where('can.internal', false)->where('unit.notes', null));
    }

    public function test_a_resident_cannot_open_someone_elses_unit(): void
    {
        $other = Unit::where('occupancy', 'alquilado')->firstOrFail();

        $this->actingAs($this->as('residente'))->get("/unidades/{$other->id}")->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Community;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role): User
    {
        $community = Community::firstOrCreate(['name' => 'Demo']);

        return User::create(['name' => $role->label(), 'email' => "{$role->value}@t.test", 'password' => 'password', 'role' => $role->value, 'community_id' => $community->id]);
    }

    public function test_superadmin_passes_any_role_check_and_others_only_their_own(): void
    {
        $this->assertTrue($this->user(Role::Superadmin)->hasRole('admin'));
        $this->assertTrue($this->user(Role::Admin)->hasRole('admin'));
        $this->assertFalse($this->user(Role::Junta)->hasRole('admin'));
    }

    public function test_owners_and_residents_are_treated_as_residents(): void
    {
        $this->assertTrue($this->user(Role::Propietario)->isResident());
        $this->assertTrue($this->user(Role::Residente)->isResident());
        $this->assertFalse($this->user(Role::Admin)->isResident());
    }

    public function test_an_owner_only_sees_incidents_they_reported(): void
    {
        $owner = $this->user(Role::Propietario);
        $other = $this->user(Role::Residente);
        $mine = $owner->reportedIncidents()->create(['title' => 'Mía', 'type' => 'Otro', 'location' => 'X']);
        $other->reportedIncidents()->create(['title' => 'Ajena', 'type' => 'Otro', 'location' => 'Y']);

        $this->actingAs($owner)->get('/incidencias')
            ->assertInertia(fn ($page) => $page->has('incidents', 1)->where('incidents.0.id', $mine->code));
        $this->actingAs($owner)->get('/incidencias/'.Incident::where('title', 'Ajena')->value('code'))->assertForbidden();
    }

    public function test_role_enum_lists_all_seven_roles(): void
    {
        $this->assertCount(7, Role::values());
    }
}

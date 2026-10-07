<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    private function payload(array $over = []): array
    {
        return $over + ['name' => 'Nuevo Vecino', 'email' => 'nuevo@habitacontrol.local', 'password' => 'Secreta123', 'role' => 'residente'];
    }

    public function test_only_admins_manage_users(): void
    {
        foreach (['residente', 'propietario', 'seguridad', 'mantenimiento', 'junta'] as $role) {
            $this->actingAs($this->as($role))->get('/usuarios')->assertForbidden();
            $this->actingAs($this->as($role))->post('/usuarios', $this->payload())->assertForbidden();
        }
        $this->actingAs($this->as('admin'))->get('/usuarios')->assertOk()->assertInertia(fn ($p) => $p->component('Users/Index')->has('users.data', 9));
    }

    public function test_creating_a_user_links_units_and_hashes_the_password(): void
    {
        $unit = Unit::where('code', 'A01-101')->firstOrFail();

        $this->actingAs($this->as('admin'))->post('/usuarios', $this->payload(['units' => [['unit_id' => $unit->id, 'relation' => 'propietario']]]))->assertSessionHasNoErrors();

        $new = User::where('email', 'nuevo@habitacontrol.local')->firstOrFail();
        $this->assertNotSame('Secreta123', $new->password);
        $this->assertSame($unit->id, $new->unit_id);
        $this->assertSame('propietario', $new->units->first()->pivot->relation);
        $this->post('/logout');
        $this->post('/login', ['email' => $new->email, 'password' => 'Secreta123'])->assertRedirect('/');
    }

    public function test_an_admin_cannot_grant_the_superadmin_role_or_edit_a_superadmin(): void
    {
        $admin = $this->as('admin');

        $this->actingAs($admin)->post('/usuarios', $this->payload(['role' => 'superadmin']))->assertSessionHasErrors('role');
        $this->actingAs($admin)->patch('/usuarios/'.$this->as('superadmin')->id, ['name' => 'Hackeado'])->assertForbidden();
        $this->actingAs($this->as('superadmin'))->post('/usuarios', $this->payload(['role' => 'admin']))->assertSessionHasNoErrors();
    }

    public function test_validation_rejects_duplicates_weak_passwords_and_units_that_do_not_exist(): void
    {
        $this->actingAs($this->as('admin'))->post('/usuarios', $this->payload(['email' => 'admin@habitacontrol.local', 'password' => 'corta']))->assertSessionHasErrors(['email', 'password']);
        $this->actingAs($this->as('admin'))->post('/usuarios', $this->payload(['units' => [['unit_id' => 99999, 'relation' => 'propietario']]]))->assertSessionHasErrors('units.0.unit_id');
    }

    public function test_a_deactivated_user_cannot_log_in_and_loses_an_open_session(): void
    {
        $resident = $this->as('residente');
        $this->actingAs($this->as('admin'))->patch("/usuarios/{$resident->id}", ['active' => false])->assertSessionHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['email' => $resident->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->actingAs($resident->fresh())->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admins_can_not_deactivate_themselves_or_change_their_own_role(): void
    {
        $admin = $this->as('admin');

        $this->actingAs($admin)->patch("/usuarios/{$admin->id}", ['active' => false])->assertStatus(422);
        $this->actingAs($admin)->patch("/usuarios/{$admin->id}", ['role' => 'residente'])->assertStatus(422);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_updating_replaces_unit_links_and_can_reset_the_password(): void
    {
        $resident = $this->as('residente');
        $unit = Unit::where('code', 'A01-102')->firstOrFail();

        $this->actingAs($this->as('admin'))->patch("/usuarios/{$resident->id}", ['password' => 'Nueva12345', 'units' => [['unit_id' => $unit->id, 'relation' => 'inquilino']]])->assertSessionHasNoErrors();

        $this->assertSame([$unit->id], $resident->units()->pluck('units.id')->all());
        $this->assertSame($unit->id, $resident->fresh()->unit_id);
        $this->post('/logout');
        $this->post('/login', ['email' => $resident->email, 'password' => 'Nueva12345'])->assertRedirect('/');
    }

    public function test_unit_search_returns_matches_for_admins_only(): void
    {
        $this->actingAs($this->as('admin'))->getJson('/unidades/buscar?q=A01-20')->assertOk()->assertJsonFragment(['code' => 'A01-204'])->assertJsonMissing(['code' => 'A01-301']);
        $this->actingAs($this->as('residente'))->getJson('/unidades/buscar?q=A01')->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Community;
use App\Models\Company;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 10:00:00');
        $this->seed(DemoSeeder::class);
    }

    private function as(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    public function test_the_seed_reproduces_the_original_balances_through_the_ledger(): void
    {
        $this->assertEquals(3700, Unit::where('code', 'A01-204')->value('balance'));
        $this->assertEquals(9300, Unit::where('code', 'A02-102')->value('balance'));
        $this->assertEquals(1250, Unit::where('code', 'A01-404')->value('balance'));
        $this->assertSame(['Moroso'], Charge::whereHas('unit', fn ($q) => $q->where('code', 'A02-102'))->where('concept', 'like', '%marzo%')->pluck('status')->all());
    }

    public function test_admin_and_board_see_finances_but_only_admin_can_change_them(): void
    {
        foreach (['admin', 'junta'] as $role) {
            $this->actingAs($this->as($role))->get('/cobros')->assertOk()
                ->assertInertia(fn ($p) => $p->component('Billing/Index')->where('can.manage', $role === 'admin')->where('stats.in_debt', 3)->has('charges.data', 11));
        }
        $this->actingAs($this->as('junta'))->post('/cobros', ['unit_id' => Unit::value('id'), 'concept' => 'X', 'amount' => 10, 'due_date' => '2026-10-01'])->assertForbidden();
    }

    public function test_security_and_maintenance_cannot_see_finances(): void
    {
        foreach (['seguridad', 'mantenimiento'] as $role) {
            $this->actingAs($this->as($role))->get('/cobros')->assertForbidden();
            $this->actingAs($this->as($role))->get('/cobros/exportar')->assertForbidden();
        }
    }

    public function test_a_resident_only_sees_their_own_units_charges_and_cannot_pay_them(): void
    {
        $resident = $this->as('residente');

        $this->actingAs($resident)->get('/cobros')->assertInertia(fn ($p) => $p->has('charges.data', 3)->where('can.manage', false)->where('communities', []));
        $charge = Charge::where('unit_id', $resident->unit_id)->firstOrFail();
        $this->actingAs($resident)->post("/cobros/{$charge->id}/pagos", ['amount' => 100, 'method' => 'Efectivo'])->assertForbidden();
    }

    public function test_admin_registers_a_unit_payment_that_clears_the_oldest_charges(): void
    {
        $unit = Unit::where('code', 'A02-102')->firstOrFail();

        $this->actingAs($this->as('admin'))->post('/cobros/pagos', ['unit_id' => $unit->id, 'amount' => 3000, 'method' => 'Transferencia', 'reference' => 'TR-9'])->assertSessionHasNoErrors();

        $this->assertEquals(6300, $unit->fresh()->balance);
        $this->assertSame('Al día', $unit->charges()->where('concept', 'like', '%marzo%')->value('status'));
        $this->assertSame(2, $unit->charges()->where('status', 'Moroso')->count() + $unit->charges()->where('status', 'Por vencer')->count());
    }

    public function test_overpaying_and_bad_methods_are_rejected(): void
    {
        $unit = Unit::where('code', 'A01-204')->firstOrFail();

        $this->actingAs($this->as('admin'))->post('/cobros/pagos', ['unit_id' => $unit->id, 'amount' => 99999, 'method' => 'Transferencia'])->assertSessionHasErrors('amount');
        $this->actingAs($this->as('admin'))->post('/cobros/pagos', ['unit_id' => $unit->id, 'amount' => 10, 'method' => 'Trueque'])->assertSessionHasErrors('method');
        $this->assertEquals(3700, $unit->fresh()->balance);
    }

    public function test_an_admin_cannot_charge_a_unit_of_another_company(): void
    {
        $foreign = Unit::create(['community_id' => Community::create(['name' => 'Ajena', 'company_id' => Company::create(['name' => 'Otra'])->id])->id, 'code' => 'Z-1']);

        $this->actingAs($this->as('admin'))->post('/cobros', ['unit_id' => $foreign->id, 'concept' => 'Cuota', 'amount' => 100, 'due_date' => '2026-10-01'])->assertSessionHasErrors('unit_id');
    }

    public function test_generating_monthly_fees_and_marking_a_charge_as_legal(): void
    {
        $admin = $this->as('admin');
        $before = Charge::count();

        $this->actingAs($admin)->post('/cobros/generar', ['community_id' => $admin->community_id, 'concept' => 'Cuota octubre', 'due_date' => '2026-10-10'])->assertSessionHasNoErrors();
        $this->assertSame($before + 97, Charge::count());

        $charge = Charge::where('status', 'Moroso')->firstOrFail();
        $this->actingAs($admin)->patch("/cobros/{$charge->id}/legal", ['legal' => true]);
        $this->assertSame('Legal', $charge->fresh()->status);
    }

    public function test_the_tabs_load_only_their_own_data(): void
    {
        $this->actingAs($this->as('admin'))->get('/cobros?tab=pagos')->assertInertia(fn ($p) => $p->where('charges', null)->has('payments.data', 5)->where('units', null));
        $this->actingAs($this->as('admin'))->get('/cobros?tab=unidades')->assertInertia(fn ($p) => $p->where('units.data.0.balance', 9300)->where('payments', null));
        $this->actingAs($this->as('admin'))->get('/cobros?status=Moroso')->assertInertia(fn ($p) => $p->has('charges.data', 2));
    }

    public function test_the_csv_export_is_scoped_and_neutralises_spreadsheet_formulas(): void
    {
        $admin = $this->as('admin');
        $this->actingAs($admin)->post('/cobros', ['unit_id' => Unit::where('code', 'A01-204')->value('id'), 'concept' => '=HYPERLINK("http://x")', 'amount' => 10, 'due_date' => '2026-10-01']);

        $csv = $this->actingAs($admin)->get('/cobros/exportar')->assertOk()->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF\"tipo\"", $csv);
        $this->assertStringContainsString("\"'=HYPERLINK", $csv);
        $this->assertStringContainsString('"pago"', $csv);

        $resident = $this->actingAs($this->as('residente'))->get('/cobros/exportar')->streamedContent();
        $this->assertStringNotContainsString('A01-301', $resident);
        $this->assertStringContainsString('A01-204', $resident);
    }

    public function test_a_units_status_is_the_worst_status_among_its_open_charges(): void
    {
        $this->actingAs($this->as('admin'))->get('/cobros?tab=unidades')->assertInertia(fn ($p) => $p
            ->where('units.data', fn ($rows) => collect($rows)->firstWhere('code', 'A02-102')['status'] === 'Moroso'
                && collect($rows)->firstWhere('code', 'A01-204')['status'] === 'Por vencer'
                && collect($rows)->firstWhere('code', 'A01-301')['status'] === 'Al día'));
    }
}

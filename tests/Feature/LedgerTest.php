<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Community;
use App\Models\Unit;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private Ledger $ledger;

    private User $admin;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 10:00:00');
        $community = Community::create(['name' => 'Demo']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'admin', 'community_id' => $community->id]);
        $this->unit = Unit::create(['community_id' => $community->id, 'code' => 'A-1', 'maintenance_fee' => 2500]);
        $this->ledger = app(Ledger::class);
    }

    private function charge(string $due, float $amount = 1000, string $concept = 'Cuota'): Charge
    {
        return $this->ledger->charge($this->admin, $this->unit, $concept, $amount, Carbon::parse($due));
    }

    public function test_a_new_charge_raises_the_unit_balance_and_gets_its_status_from_the_due_date(): void
    {
        $this->assertSame('Por vencer', $this->charge('2026-09-30')->status);      // vence hoy: aún no está vencido
        $this->assertSame('Vencido', $this->charge('2026-09-15')->status);         // 15 días
        $this->assertSame('Moroso', $this->charge('2026-08-01')->status);          // más de 30 días

        $this->assertEquals(3000, $this->unit->fresh()->balance);
    }

    public function test_a_payment_covers_the_oldest_charges_first_and_splits_across_them(): void
    {
        $old = $this->charge('2026-07-30', 1000, 'Julio');
        $mid = $this->charge('2026-09-05', 1000, 'Septiembre temprano');
        $new = $this->charge('2026-09-30', 1000, 'Septiembre');

        $payments = $this->ledger->pay($this->admin, $this->unit, 1500, 'Efectivo', 'RC-1');

        $this->assertCount(2, $payments);                                          // una fila por cobro cubierto
        $this->assertSame(['RC-1'], $payments->pluck('reference')->unique()->values()->all());
        $this->assertSame('Al día', $old->fresh()->status);
        $this->assertSame('Vencido', $mid->fresh()->status);                       // quedan 500 pendientes
        $this->assertSame('Por vencer', $new->fresh()->status);
        $this->assertEquals(1500, $this->unit->fresh()->balance);
    }

    public function test_paying_a_specific_charge_only_touches_that_charge(): void
    {
        $old = $this->charge('2026-08-30', 1000, 'Agosto');
        $new = $this->charge('2026-09-30', 1000, 'Septiembre');

        $this->ledger->pay($this->admin, $this->unit, 1000, 'Efectivo', null, $new);

        $this->assertSame('Al día', $new->fresh()->status);
        $this->assertNotSame('Al día', $old->fresh()->status);

        $this->expectException(ValidationException::class);
        $this->ledger->pay($this->admin, $this->unit, 1, 'Efectivo', null, $new); // ya no debe nada
    }

    public function test_a_payment_can_not_exceed_the_pending_balance_or_be_zero(): void
    {
        $this->charge('2026-09-30', 1000);

        foreach ([1000.01, 0, -5] as $bad) {
            try {
                $this->ledger->pay($this->admin, $this->unit, $bad, 'Efectivo');
                $this->fail("Se aceptó el monto {$bad}");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('amount', $e->errors());
            }
        }
        $this->assertEquals(1000, $this->unit->fresh()->balance);
    }

    public function test_cents_do_not_accumulate_floating_point_errors(): void
    {
        $c = $this->charge('2026-09-30', 100.10);
        $this->ledger->pay($this->admin, $this->unit, 0.10, 'Efectivo');
        $this->ledger->pay($this->admin, $this->unit, 100.00, 'Efectivo');

        $this->assertSame('Al día', $c->fresh()->status);
        $this->assertEquals(0, $this->unit->fresh()->balance);
    }

    public function test_the_daily_refresh_ages_charges_and_leaves_paid_and_legal_ones_alone(): void
    {
        $due = $this->charge('2026-10-05', 1000, 'Octubre');
        $paid = $this->charge('2026-10-05', 500, 'Pagada');
        $this->ledger->pay($this->admin, $this->unit, 500, 'Efectivo', null, $paid);
        $legal = $this->charge('2026-10-05', 700, 'Legal');
        $this->ledger->setLegal($legal, $this->admin, true);

        Carbon::setTestNow('2026-10-20');
        $this->assertSame(1, $this->ledger->refreshStatuses());
        $this->assertSame('Vencido', $due->fresh()->status);

        Carbon::setTestNow('2026-12-01');
        $this->ledger->refreshStatuses();
        $this->assertSame('Moroso', $due->fresh()->status);
        $this->assertSame('Al día', $paid->fresh()->status);
        $this->assertSame('Legal', $legal->fresh()->status);
    }

    public function test_legal_is_manual_reversible_and_cleared_by_full_payment(): void
    {
        $c = $this->charge('2026-06-01', 1000);
        $this->assertSame('Moroso', $c->status);

        $this->ledger->setLegal($c, $this->admin, true);
        $this->assertSame('Legal', $c->fresh()->status);
        $this->ledger->setLegal($c->fresh(), $this->admin, false);
        $this->assertSame('Moroso', $c->fresh()->status);

        $this->ledger->setLegal($c->fresh(), $this->admin, true);
        $this->ledger->pay($this->admin, $this->unit, 1000, 'Transferencia');
        $this->assertSame('Al día', $c->fresh()->status);

        $this->expectException(ValidationException::class);
        $this->ledger->setLegal($c->fresh(), $this->admin, true);
    }

    public function test_monthly_generation_is_idempotent_and_skips_units_without_a_fee(): void
    {
        Unit::create(['community_id' => $this->unit->community_id, 'code' => 'A-2', 'maintenance_fee' => 3000]);
        Unit::create(['community_id' => $this->unit->community_id, 'code' => 'A-3', 'maintenance_fee' => 0]);
        $community = $this->unit->community;

        $this->assertSame(2, $this->ledger->generateMonthly($this->admin, $community, 'Cuota octubre', Carbon::parse('2026-10-10')));
        $this->assertSame(0, $this->ledger->generateMonthly($this->admin, $community, 'Cuota octubre', Carbon::parse('2026-10-15')));
        $this->assertEquals(3000, Unit::where('code', 'A-2')->value('balance'));
        $this->assertDatabaseHas('activity_logs', ['subject_type' => Unit::class, 'action' => 'Cargo creado']);
    }
}

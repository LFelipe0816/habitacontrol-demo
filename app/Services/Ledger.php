<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\Community;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Libro de cuentas de las unidades. Los cobros son el debe y los pagos el haber; el saldo de la unidad
 * (`units.balance`) y el estado de cada cobro se derivan de ahí y nunca se editan a mano.
 * Los importes se calculan en centavos enteros para no arrastrar errores de punto flotante.
 */
class Ledger
{
    public function charge(User $actor, Unit $unit, string $concept, float $amount, CarbonInterface $due): Charge
    {
        return DB::transaction(function () use ($actor, $unit, $concept, $amount, $due) {
            $charge = $unit->charges()->create(['concept' => $concept, 'amount' => $amount, 'due_date' => $due, 'status' => 'Por vencer']);
            $this->refreshCharge($charge);
            $this->syncBalance($unit);
            $this->log($unit, $actor, 'Cargo creado', "{$concept} por {$this->fmt($amount)} aplicado a {$unit->code}.");

            return $charge->fresh();
        });
    }

    /** Genera la cuota de mantenimiento de cada unidad del residencial; no duplica si ya existe ese concepto en el mes. */
    public function generateMonthly(User $actor, Community $community, string $concept, CarbonInterface $due): int
    {
        $created = 0;
        $community->units()->where('maintenance_fee', '>', 0)->each(function (Unit $unit) use ($actor, $concept, $due, &$created) {
            $exists = $unit->charges()->where('concept', $concept)->whereYear('due_date', $due->year)->whereMonth('due_date', $due->month)->exists();
            if (! $exists) {
                $this->charge($actor, $unit, $concept, $unit->maintenance_fee, $due);
                $created++;
            }
        });

        return $created;
    }

    /**
     * Registra un pago. Con `$first` se aplica solo a ese cobro; sin él se reparte entre los cobros abiertos del más
     * antiguo al más reciente. Genera una fila de pago por cobro cubierto, todas con el mismo recibo.
     *
     * @return Collection<int, Payment>
     */
    public function pay(User $actor, Unit $unit, float $amount, string $method, ?string $reference = null, ?Charge $first = null, ?CarbonInterface $at = null): Collection
    {
        return DB::transaction(function () use ($actor, $unit, $amount, $method, $reference, $first, $at) {
            Unit::whereKey($unit->id)->lockForUpdate()->first();

            $open = $this->openCharges($unit);
            $limit = $first ? ($open->firstWhere('id', $first->id) ? $this->remaining($open->firstWhere('id', $first->id)) : 0) : $open->sum(fn ($c) => $this->remaining($c));
            $cents = $this->cents($amount);

            if ($cents <= 0 || $cents > $limit) {
                throw ValidationException::withMessages(['amount' => $limit <= 0 ? 'No hay saldo pendiente por cobrar.' : 'El monto debe ser mayor que 0 y no superar el saldo pendiente ('.$this->fmt($limit / 100).').']);
            }

            $receipt = $reference ?: 'RC-'.Str::upper(Str::random(6));
            // Con `$first` el pago cubre solo ese cobro; sin él, cubre los abiertos del más antiguo al más reciente.
            $targets = $first ? $open->where('id', $first->id) : $open;
            $payments = collect();

            foreach ($targets as $charge) {
                if ($cents <= 0) {
                    break;
                }
                $part = min($cents, $this->remaining($charge));
                $payment = $charge->payments()->create(['unit_id' => $unit->id, 'user_id' => $actor->id, 'amount' => $part / 100, 'method' => $method, 'reference' => $receipt]);
                if ($at) {
                    $payment->forceFill(['created_at' => $at])->saveQuietly();
                }
                $cents -= $part;
                $payments->push($payment);
                $this->refreshCharge($charge);
            }

            $this->syncBalance($unit);
            $this->log($unit, $actor, 'Pago registrado', "{$unit->code} pagó {$this->fmt($amount)} ({$method}, {$receipt}).");

            return $payments;
        });
    }

    /** Marca (o desmarca) un cobro como en proceso legal; solo tiene sentido si aún tiene saldo. */
    public function setLegal(Charge $charge, User $actor, bool $legal): void
    {
        $charge->loadSum('payments', 'amount');
        if ($this->remaining($charge) <= 0) {
            throw ValidationException::withMessages(['legal' => 'Un cobro pagado no puede pasar a proceso legal.']);
        }
        $charge->update(['status' => $legal ? 'Legal' : 'Por vencer']);
        $this->refreshCharge($charge);
        $this->log($charge->unit, $actor, $legal ? 'Cobro enviado a legal' : 'Cobro retirado de legal', "{$charge->concept} de {$charge->unit->code}.");
    }

    /** Recalcula los estados por antigüedad; lo ejecuta el scheduler cada día. Devuelve cuántos cobros cambiaron. */
    public function refreshStatuses(): int
    {
        $changed = 0;
        Charge::whereNotIn('status', ['Al día', 'Legal'])->withSum('payments', 'amount')->each(function (Charge $charge) use (&$changed) {
            $charge->status !== $this->statusFor($charge) && ++$changed && $this->refreshCharge($charge);
        });

        return $changed;
    }

    public function statusFor(Charge $charge): string
    {
        if ($this->remaining($charge) <= 0) {
            return 'Al día';
        }
        if ($charge->status === 'Legal') {
            return 'Legal';
        }
        $daysLate = (int) $charge->due_date->startOfDay()->diffInDays(today(), false);

        return match (true) {
            $daysLate <= 0 => 'Por vencer',
            $daysLate <= Charge::MOROSO_AFTER_DAYS => 'Vencido',
            default => 'Moroso',
        };
    }

    public function syncBalance(Unit $unit): void
    {
        $unit->forceFill(['balance' => $this->openCharges($unit)->sum(fn ($c) => $this->remaining($c)) / 100])->save();
    }

    private function refreshCharge(Charge $charge): void
    {
        $charge->loadSum('payments', 'amount');
        $status = $this->statusFor($charge);
        if ($charge->status !== $status) {
            $charge->update(['status' => $status]);
        }
    }

    /** @return Collection<int, Charge> cobros con saldo, del más antiguo al más reciente */
    private function openCharges(Unit $unit): Collection
    {
        return $unit->charges()->withSum('payments', 'amount')->orderBy('due_date')->orderBy('id')->get()->filter(fn ($c) => $this->remaining($c) > 0)->values();
    }

    /** Centavos pendientes de un cobro con `payments_sum_amount` cargado. */
    private function remaining(Charge $charge): int
    {
        return max(0, $this->cents($charge->amount) - $this->cents($charge->payments_sum_amount ?? 0));
    }

    private function cents(float|string|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fmt(float $amount): string
    {
        return 'RD$'.number_format($amount, 2, ',', '.');
    }

    private function log(Unit $unit, User $actor, string $action, string $detail): void
    {
        $unit->activities()->create(['community_id' => $unit->community_id, 'user_id' => $actor->id, 'action' => $action, 'detail' => $detail]);
    }
}

<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /** El personal ve las unidades de sus residenciales; propietarios y residentes, solo las suyas. */
    public function view(User $user, Unit $unit): bool
    {
        if ($user->isResident()) {
            return $user->livesIn($unit);
        }

        return Community::visibleTo($user)->whereKey($unit->community_id)->exists();
    }

    /** Cargos, pagos y cobranza de la unidad: solo administración, dentro de sus residenciales. */
    public function manageFinances(User $user, Unit $unit): bool
    {
        return $user->hasRole('admin') && $this->view($user, $unit);
    }

    /** Notas internas de la administración sobre la unidad; el residente no debe verlas. */
    public function viewInternal(User $user, Unit $unit): bool
    {
        return $user->isStaff() && $this->view($user, $unit);
    }
}

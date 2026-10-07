<?php

namespace App\Policies;

use App\Models\Charge;
use App\Models\User;

class ChargePolicy
{
    /** Administración y junta ven las finanzas; propietarios y residentes, las de sus unidades. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'junta') || $user->isResident();
    }

    public function view(User $user, Charge $charge): bool
    {
        return Charge::visibleTo($user)->whereKey($charge->id)->exists();
    }

    /** Registrar pagos, marcar cobros como legales: administración, dentro de sus residenciales. */
    public function manage(User $user, Charge $charge): bool
    {
        return $user->hasRole('admin') && $this->view($user, $charge);
    }
}

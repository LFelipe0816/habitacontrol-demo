<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\ServiceAsset;
use App\Models\User;

class ServiceAssetPolicy
{
    /** Administración, mantenimiento y junta directiva consultan los servicios generales. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'mantenimiento', 'junta');
    }

    public function view(User $user, ServiceAsset $asset): bool
    {
        return $this->viewAny($user) && Community::visibleTo($user)->whereKey($asset->community_id)->exists();
    }

    /** Registrar lecturas, programar y completar mantenimientos. La junta solo consulta. */
    public function operate(User $user, ServiceAsset $asset): bool
    {
        return $user->hasRole('admin', 'mantenimiento') && $this->view($user, $asset);
    }

    /** Alta y edición completa del activo (ubicación, proveedor, rutina). */
    public function configure(User $user, ServiceAsset $asset): bool
    {
        return $user->hasRole('admin') && $this->view($user, $asset);
    }
}

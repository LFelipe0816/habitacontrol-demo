<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function view(User $user, Incident $incident): bool
    {
        return Incident::visibleTo($user)->whereKey($incident->id)->exists();
    }

    /** Cambiar estado, prioridad, notas y evidencias: administración, o el personal al que se asignó el caso. */
    public function manage(User $user, Incident $incident): bool
    {
        if ($user->hasRole('admin')) {
            return $this->view($user, $incident);
        }

        return $user->hasRole('mantenimiento', 'seguridad')
            && ($incident->assignee_id === $user->id || $incident->assignee_role === $user->role);
    }

    public function assign(User $user, Incident $incident): bool
    {
        return $user->hasRole('admin') && $this->view($user, $incident);
    }

    /** Las notas internas y la bitácora completa son solo para el personal; quien reportó ve las respuestas. */
    public function comment(User $user, Incident $incident): bool
    {
        return $this->view($user, $incident) && ($user->isStaff() ? $user->hasRole('admin', 'mantenimiento', 'seguridad') : $incident->reporter_id === $user->id);
    }

    public function viewInternal(User $user, Incident $incident): bool
    {
        return $user->isStaff() && $this->view($user, $incident);
    }

    /** Solo quien reportó confirma, y solo cuando el caso está resuelto. */
    public function confirm(User $user, Incident $incident): bool
    {
        return $incident->reporter_id === $user->id && $incident->status === 'Resuelta';
    }
}

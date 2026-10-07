<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function manage(User $user, User $target): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }
        if ($target->role === 'superadmin' && $user->role !== 'superadmin') {
            return false;
        }

        return $user->hasRole('superadmin') || Community::visibleTo($user)->whereKey($target->community_id)->exists();
    }
}

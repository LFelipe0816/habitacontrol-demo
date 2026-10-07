<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class CommunityPolicy
{
    /** Solo el personal consulta el portafolio, y únicamente el de su administradora. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Community $community): bool
    {
        return $user->isStaff() && Community::visibleTo($user)->whereKey($community->id)->exists();
    }
}

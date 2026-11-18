<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy extends BasePolicy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        return $user->permitted('role.list-all');
    }
}

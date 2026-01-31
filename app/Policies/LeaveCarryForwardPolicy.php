<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeaveCarryForward;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\LeaveCarryForwardPolicy as BasePolicy;

class LeaveCarryForwardPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->can('view_any_leave::carry::forward');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveCarryForward $leaveCarryForward): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->can('view_leave::carry::forward');
    }
}

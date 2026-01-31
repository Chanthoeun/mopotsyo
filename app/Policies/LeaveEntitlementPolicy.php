<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeaveEntitlement;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\LeaveEntitlementPolicy as BasePolicy;

class LeaveEntitlementPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->has_entitlement || $user->can('view_any_leave::entitlement');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveEntitlement $leaveEntitlement): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->has_entitlement || $user->can('view_leave::entitlement');
    }
}

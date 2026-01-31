<?php

namespace App\Policies;

use App\Models\User;
use App\Models\OverTime;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\OverTimePolicy as BasePolicy;

class OverTimePolicy extends BasePolicy
{

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        return $user->can('view_any_over::time');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OverTime $overTime): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if ($user->id === $overTime->user_id) {
            return $user->can('view_over::time');
        }

        // Allow view if the user is an approver for this request
        if ($overTime->approvalSteps()->where('approver_id', $user->id)->exists()) {
            return $user->can('view_over::time');
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (empty($user->supervisor))
            return false;

        return $user->can('create_over::time');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OverTime $overTime): bool
    {
        if ($overTime->status === \App\Enums\Status::CREATED && $user->id == $overTime->user_id) {
            return $user->can('update_over::time');
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OverTime $overTime): bool
    {
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, OverTime $overTime): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, OverTime $overTime): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        if ($overTime->status === \App\Enums\Status::PENDING && $user->id == $overTime->user_id) {
            return $user->can('restore_over::time');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_over::time');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, OverTime $overTime): bool
    {
        return $user->can('replicate_over::time');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_over::time');
    }

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, OverTime $overTime): bool
    {
        return $overTime->status === \App\Enums\Status::PENDING && $overTime->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, OverTime $overTime): bool
    {
        return $overTime->status === \App\Enums\Status::PENDING && $overTime->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, OverTime $overTime): bool
    {
        return ($overTime->isSubmitted() || $overTime->isApproved()) && ($user->id === $overTime->user_id || $user->hasRole('super_admin'));
    }
}

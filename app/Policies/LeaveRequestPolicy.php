<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeaveRequest;

use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Trunc;

use App\Policies\Base\LeaveRequestPolicy as BasePolicy;

class LeaveRequestPolicy extends BasePolicy
{

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if (empty($user->contract))
            return false;

        return $user->can('view_any_leave::request');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if ($user->id === $leaveRequest->user_id) {
            return $user->can('view_leave::request');
        }

        // Allow view if the user is an approver for this request
        if ($leaveRequest->approvalSteps()->where('approver_id', $user->id)->exists()) {
            return $user->can('view_leave::request');
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

        if (empty($user->contract))
            return false;

        if (empty($user->contract->contractType->allow_leave_request))
            return false;

        return $user->can('create_leave::request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($leaveRequest->status === 'pending' && $user->id == $leaveRequest->user_id) {
            return $user->can('update_leave::request');
        }
        return false;

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
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
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
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
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        if ($leaveRequest->status === 'pending' && $user->id == $leaveRequest->user_id) {
            return $user->can('restore_leave::request');
        }

        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->can('restore_any_leave::request');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->can('replicate_leave::request');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        return $user->can('reorder_leave::request');
    }

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->status === \App\Enums\Status::PENDING && $leaveRequest->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->status === \App\Enums\Status::PENDING && $leaveRequest->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, LeaveRequest $leaveRequest): bool
    {
        return ($leaveRequest->isSubmitted() || $leaveRequest->isApproved()) && ($user->id === $leaveRequest->user_id || $user->hasRole('super_admin'));
    }

}

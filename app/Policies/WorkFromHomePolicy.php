<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\WorkFromHomePolicy as BasePolicy;

class WorkFromHomePolicy extends BasePolicy
{

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if (app(SettingOptions::class)->allow_work_from_home == true) {
            return $user->can('view_any_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkFromHome $workFromHome): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if (app(SettingOptions::class)->allow_work_from_home == true) {
            if ($user->id == $workFromHome->user_id) {
                return $user->can('view_work::from::home');
            }

            // Allow view if the user is an approver for this request
            if ($workFromHome->approvalSteps()->where('approver_id', $user->id)->exists()) {
                return $user->can('view_work::from::home');
            }
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (app(SettingOptions::class)->allow_work_from_home == true) {
            return $user->can('create_work::from::home');
        }
        return false;
    }

    public function update(User $user, WorkFromHome $workFromHome): bool
    {
        if ($user->id == $workFromHome->user_id) {
            return $workFromHome->status === \App\Enums\Status::CREATED;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkFromHome $workFromHome): bool
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
    public function forceDelete(User $user, WorkFromHome $workFromHome): bool
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
    public function restore(User $user, WorkFromHome $workFromHome): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        if ($workFromHome->status === \App\Enums\Status::PENDING && $user->id == $workFromHome->user_id) {
            return $user->can('restore_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_work::from::home');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, WorkFromHome $workFromHome): bool
    {
        return $user->can('replicate_work::from::home');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_work::from::home');
    }

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, WorkFromHome $workFromHome): bool
    {
        return $workFromHome->status === \App\Enums\Status::PENDING && $workFromHome->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, WorkFromHome $workFromHome): bool
    {
        return $workFromHome->status === \App\Enums\Status::PENDING && $workFromHome->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, WorkFromHome $workFromHome): bool
    {
        return ($workFromHome->isSubmitted() || $workFromHome->isApproved()) && ($user->id === $workFromHome->user_id || $user->hasRole('super_admin'));
    }
}

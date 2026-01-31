<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SwitchWorkDay;
use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\SwitchWorkDayPolicy as BasePolicy;

class SwitchWorkDayPolicy extends BasePolicy
{

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if (app(SettingOptions::class)->allow_switch_day_work == true) {
            return $user->can('view_any_switch::work::day');
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if (app(SettingOptions::class)->allow_switch_day_work == true) {
            if ($user->id == $switchWorkDay->user_id) {
                return $user->can('view_switch::work::day');
            }

            // Allow view if the user is an approver for this request
            if ($switchWorkDay->approvalSteps()->where('approver_id', $user->id)->exists()) {
                return $user->can('view_switch::work::day');
            }
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (app(SettingOptions::class)->allow_switch_day_work == true && $user->contract && strtolower($user->contract->contractType->abbr) == 'ptc') {
            return $user->can('create_switch::work::day');
        }
        return false;
    }

    public function update(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        if ($user->id == $switchWorkDay->user_id) {
            return $switchWorkDay->status === \App\Enums\Status::CREATED;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SwitchWorkDay $switchWorkDay): bool
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
    public function forceDelete(User $user, SwitchWorkDay $switchWorkDay): bool
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
    public function restore(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        if ($switchWorkDay->status === \App\Enums\Status::PENDING && $user->id == $switchWorkDay->user_id) {
            return $user->can('restore_switch::work::day');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_switch::work::day');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $user->can('replicate_switch::work::day');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_switch::work::day');
    }

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $switchWorkDay->status === \App\Enums\Status::PENDING && $switchWorkDay->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $switchWorkDay->status === \App\Enums\Status::PENDING && $switchWorkDay->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return ($switchWorkDay->isSubmitted() || $switchWorkDay->isApproved()) && ($user->id === $switchWorkDay->user_id || $user->hasRole('super_admin'));
    }
}

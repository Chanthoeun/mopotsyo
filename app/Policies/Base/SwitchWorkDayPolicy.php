<?php

namespace App\Policies\Base;

use App\Models\User;
use App\Models\SwitchWorkDay;
use Illuminate\Auth\Access\HandlesAuthorization;

class SwitchWorkDayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_switch::work::day');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $user->can('view_switch::work::day');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_switch::work::day');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $user->can('update_switch::work::day');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $user->can('delete_switch::work::day');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_switch::work::day');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $user->can('force_delete_switch::work::day');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_switch::work::day');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        return $user->can('restore_switch::work::day');
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
}

<?php

namespace App\Policies;


use App\Models\User;
use App\Models\PurchaseRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\PurchaseRequestPolicy as BasePolicy;

class PurchaseRequestPolicy extends BasePolicy
{

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        return $user->can('view_any_purchase::request');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->hasRole(['super_admin', 'human_resource']))
            return true;

        if ($user->id == $purchaseRequest->user_id) {
            return $user->can('view_purchase::request');
        }

        // Allow view if the user is an approver for this request
        if ($purchaseRequest->approvalSteps()->where('approver_id', $user->id)->exists()) {
            return $user->can('view_purchase::request');
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_purchase::request');
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->id == $purchaseRequest->user_id) {
            return $purchaseRequest->status === \App\Enums\Status::CREATED;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
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
    public function forceDelete(User $user, PurchaseRequest $purchaseRequest): bool
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
    public function restore(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->hasRole('super_admin'))
            return true;

        if ($purchaseRequest->status === \App\Enums\Status::PENDING && $user->id == $purchaseRequest->user_id) {
            return $user->can('restore_purchase::request');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_purchase::request');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('replicate_purchase::request');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_purchase::request');
    }

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $purchaseRequest->status === \App\Enums\Status::PENDING && $purchaseRequest->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $purchaseRequest->status === \App\Enums\Status::PENDING && $purchaseRequest->canBeApprovedBy($user);
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return ($purchaseRequest->isSubmitted() || $purchaseRequest->isApproved()) && ($user->id === $purchaseRequest->user_id || $user->hasRole('super_admin'));
    }
}

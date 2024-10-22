<?php

namespace App\Policies;

use App\Enums\ApprovalStatuEnum;
use App\Models\User;
use App\Models\PurchaseRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_purchase::request');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->can('view_purchase::request');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_purchase::request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if($purchaseRequest->approvalStatus->status == 'Created' && $user->id == $purchaseRequest->user_id){
            return $user->can('update_purchase::request');
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if($purchaseRequest->approvalStatus->status == 'Created' && $user->id == $purchaseRequest->user_id){
            return $user->can('delete_purchase::request');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_purchase::request');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if($purchaseRequest->approvalStatus->status == 'Created' && $user->id == $purchaseRequest->user_id){
            return $user->can('force_delete_purchase::request');
        }
        return false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_purchase::request');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if($purchaseRequest->approvalStatus->status == 'Created' && $user->id == $purchaseRequest->user_id){
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
        if($purchaseRequest->isSubmitted() && !$purchaseRequest->isApprovalCompleted() && !$purchaseRequest->isDiscarded()){
            $nextStep = $purchaseRequest->nextApprovalStep();
            $approval = $purchaseRequest->user->approvers->where('model_type', get_class($purchaseRequest))->where('role_id', $nextStep->role_id)->first();
            if($approval && $approval->approver_id == $user->id){
                return $purchaseRequest->canBeApprovedBy($user);
            }            
        }
        
        return false;
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, PurchaseRequest $purchaseRequest): bool
    {      
        if($purchaseRequest->isSubmitted() && !$purchaseRequest->isApprovalCompleted() && !$purchaseRequest->isDiscarded()){
            $nextStep = $purchaseRequest->nextApprovalStep();
            $approval = $purchaseRequest->user->approvers->where('model_type', get_class($purchaseRequest))->where('role_id', $nextStep->role_id)->first();
            if($approval && $approval->approver_id == $user->id){
                return $purchaseRequest->canBeApprovedBy($user);
            }            
        }
        
        return false;
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, PurchaseRequest $purchaseRequest): bool
    {                           
        if($purchaseRequest->isRejected() && !$purchaseRequest->isDiscarded()){
            $nextStep = $purchaseRequest->nextApprovalStep();
            $approval = $purchaseRequest->user->approvers->where('model_type', get_class($purchaseRequest))->where('role_id', $nextStep->role_id)->first();
            if($purchaseRequest->user_id == $user->id || ($approval && $approval->approver_id == $user->id)){
                return true;
            }                  
        }       

        return false;
    }
}

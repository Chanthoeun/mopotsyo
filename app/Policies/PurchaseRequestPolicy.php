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
        $nextStep = $purchaseRequest->nextApprovalStep();
        if($nextStep){
            $getApprover = $purchaseRequest->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($purchaseRequest->isSubmitted() &&
                    !$purchaseRequest->isApprovalCompleted() &&
                    !$purchaseRequest->isDiscarded() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($purchaseRequest->canBeApprovedBy($user) && $purchaseRequest->isSubmitted() &&
                    !$purchaseRequest->isApprovalCompleted() &&
                    !$purchaseRequest->isDiscarded()) return true;
                }            
            }
        }    
        // dump($nextStep);
        
        return false;
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, PurchaseRequest $purchaseRequest): bool
    {      
        
        $nextStep = $purchaseRequest->nextApprovalStep();
        if($nextStep){
            $getApprover = $purchaseRequest->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($purchaseRequest->isSubmitted() &&
                    !$purchaseRequest->isApprovalCompleted() &&
                    !$purchaseRequest->isRejected() &&
                    !$purchaseRequest->isDiscarded() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($purchaseRequest->canBeApprovedBy($user) && $purchaseRequest->isSubmitted() &&
                    !$purchaseRequest->isApprovalCompleted() &&
                    !$purchaseRequest->isRejected() &&
                    !$purchaseRequest->isDiscarded()) return true;
                }            
            }
        }else{
            if($purchaseRequest->canBeApprovedBy($user) && $purchaseRequest->isSubmitted() &&
                !$purchaseRequest->isApprovalCompleted() &&
                !$purchaseRequest->isRejected() &&
                !$purchaseRequest->isDiscarded()) return true;
        }
        
        return false;
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, PurchaseRequest $purchaseRequest): bool
    {                           
        // dd($leaveRequest->nextApprovalStep()->role_id);
        $nextStep = $purchaseRequest->nextApprovalStep();
        if($nextStep){
            $getApprover = $purchaseRequest->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($purchaseRequest->isRejected() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($purchaseRequest->canBeApprovedBy($user) && $purchaseRequest->isRejected()) return true;
                }            
            }
        }else{
            if($purchaseRequest->canBeApprovedBy($user) && $purchaseRequest->isRejected()) return true;
        }   
                
        return false;
    }
}

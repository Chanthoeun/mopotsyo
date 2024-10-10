<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkFromHomePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if(app(SettingOptions::class)->allow_work_from_home == true){
            return $user->can('view_any_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkFromHome $workFromHome): bool
    {
        if(app(SettingOptions::class)->allow_work_from_home == true){
            return $user->can('view_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if(app(SettingOptions::class)->allow_work_from_home == true){
            return $user->can('create_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkFromHome $workFromHome): bool
    {
        if($workFromHome->approvalStatus->status == 'Created' && $user->id == $workFromHome->user_id){
            return $user->can('update_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkFromHome $workFromHome): bool
    {
        if($workFromHome->approvalStatus->status == 'Created' && $user->id == $workFromHome->user_id){
            return $user->can('delete_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_work::from::home');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, WorkFromHome $workFromHome): bool
    {
        if($workFromHome->approvalStatus->status == 'Created' && $user->id == $workFromHome->user_id){
            return $user->can('force_delete_work::from::home');
        }
        return false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_work::from::home');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, WorkFromHome $workFromHome): bool
    {
        if($workFromHome->approvalStatus->status == 'Created' && $user->id == $workFromHome->user_id){
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
        $nextStep = $workFromHome->nextApprovalStep();
        if($nextStep){
            $getApprover = $workFromHome->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($workFromHome->isSubmitted() &&
                    !$workFromHome->isApprovalCompleted() &&
                    !$workFromHome->isDiscarded() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($workFromHome->canBeApprovedBy($user) && $workFromHome->isSubmitted() &&
                    !$workFromHome->isApprovalCompleted() &&
                    !$workFromHome->isDiscarded()) return true;
                }            
            }
        }    
        // dump($nextStep);
        
        return false;
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, WorkFromHome $workFromHome): bool
    {           
        $nextStep = $workFromHome->nextApprovalStep();
        if($nextStep){
            $getApprover = $workFromHome->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($workFromHome->isSubmitted() &&
                    !$workFromHome->isApprovalCompleted() &&
                    !$workFromHome->isRejected() &&
                    !$workFromHome->isDiscarded() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($workFromHome->canBeApprovedBy($user) && $workFromHome->isSubmitted() &&
                    !$workFromHome->isApprovalCompleted() &&
                    !$workFromHome->isRejected() &&
                    !$workFromHome->isDiscarded()) return true;
                }            
            }
        }else{
            if($workFromHome->canBeApprovedBy($user) && $workFromHome->isSubmitted() &&
                !$workFromHome->isApprovalCompleted() &&
                !$workFromHome->isRejected() &&
                !$workFromHome->isDiscarded()) return true;
        }
        
        return false;
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, WorkFromHome $workFromHome): bool
    {                           
        // dd($leaveRequest->nextApprovalStep()->role_id);
        $nextStep = $workFromHome->nextApprovalStep();
        if($nextStep){
            $getApprover = $workFromHome->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($workFromHome->isRejected() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($workFromHome->canBeApprovedBy($user) && $workFromHome->isRejected()) return true;
                }            
            }
        }else{
            if($workFromHome->canBeApprovedBy($user) && $workFromHome->isRejected()) return true;
        }   
                
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\ProcessApprover;
use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaveRequestPolicy
{
    use HandlesAuthorization;    

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if($user->hasRole('super_admin')) return true;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;

        return $user->can('view_any_leave::request');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if($user->hasRole('super_admin')) return true;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;

        return $user->can('view_leave::request');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if(empty($user->supervisor)) return false;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;

        return $user->can('create_leave::request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if(empty($user->supervisor)) return false;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;
        
        return $user->can('update_leave::request');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        if($user->hasRole('super_admin')) return true;

        if(empty($user->supervisor)) return false;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;

        return $user->can('delete_leave::request');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        if($user->hasRole('super_admin')) return true;

        if(empty($user->supervisor)) return false;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;

        return $user->can('delete_any_leave::request');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        if($user->hasRole('super_admin')) return true;

        if(empty($user->supervisor)) return false;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;

        return $user->can('force_delete_leave::request');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        if($user->hasRole('super_admin')) return true;

        if(empty($user->supervisor)) return false;

        if(empty($user->contract)) return false;

        if(empty($user->contract->contractType->allow_leave_request)) return false;
        
        return $user->can('force_delete_any_leave::request');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        if($user->hasRole('super_admin')) return true;

        return $user->can('restore_leave::request');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        if($user->hasRole('super_admin')) return true;

        return $user->can('restore_any_leave::request');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, LeaveRequest $leaveRequest): bool
    {
        if($user->hasRole('super_admin')) return true;

        return $user->can('replicate_leave::request');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        if($user->hasRole('super_admin')) return true;
        
        return $user->can('reorder_leave::request');
    }

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if(empty($leaveRequest->leaveType->rules->count())){
            if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isSubmitted() &&
                    !$leaveRequest->isApprovalCompleted() &&
                    !$leaveRequest->isDiscarded()) return true;
        }

        $nextStep = $leaveRequest->nextApprovalStep();
        if($nextStep){
            $getApprover = ProcessApprover::where('leave_request_id', $leaveRequest->id)->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->user){
                    if($leaveRequest->isSubmitted() &&
                    !$leaveRequest->isApprovalCompleted() &&
                    !$leaveRequest->isDiscarded() && $user->id == $getApprover->user->id) return true;
                }else{
                    if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isSubmitted() &&
                    !$leaveRequest->isApprovalCompleted() &&
                    !$leaveRequest->isDiscarded()) return true;
                }            
            }
        }    
        // dump($nextStep);
        
        return false;
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {      
        if(empty($leaveRequest->leaveType->rules->count())){
            if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isSubmitted() &&
                !$leaveRequest->isApprovalCompleted() &&
                !$leaveRequest->isRejected() &&
                !$leaveRequest->isDiscarded()) return true;
        }  

        $nextStep = $leaveRequest->nextApprovalStep();
        if($nextStep){
            $getApprover = ProcessApprover::where('leave_request_id', $leaveRequest->id)->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->user){
                    if($leaveRequest->isSubmitted() &&
                    !$leaveRequest->isApprovalCompleted() &&
                    !$leaveRequest->isRejected() &&
                    !$leaveRequest->isDiscarded() && $user->id == $getApprover->user->id) return true;
                }else{
                    if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isSubmitted() &&
                    !$leaveRequest->isApprovalCompleted() &&
                    !$leaveRequest->isRejected() &&
                    !$leaveRequest->isDiscarded()) return true;
                }            
            }
        }else{
            if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isSubmitted() &&
                !$leaveRequest->isApprovalCompleted() &&
                !$leaveRequest->isRejected() &&
                !$leaveRequest->isDiscarded()) return true;
        }
        
        return false;
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, LeaveRequest $leaveRequest): bool
    {                           
        if(empty($leaveRequest->leaveType->rules->count())){
            if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isRejected()) return true;
        } 

        // dd($leaveRequest->nextApprovalStep()->role_id);
        $nextStep = $leaveRequest->nextApprovalStep();
        if($nextStep){
            $getApprover = ProcessApprover::where('leave_request_id', $leaveRequest->id)->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->user){
                    if($leaveRequest->isRejected() && $user->id == $getApprover->user->id) return true;
                }else{
                    if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isRejected()) return true;
                }            
            }
        }else{
            if($leaveRequest->canBeApprovedBy($user) && $leaveRequest->isRejected()) return true;
        }   
                
        
        return false;
    }
    
}

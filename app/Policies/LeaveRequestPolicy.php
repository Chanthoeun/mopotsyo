<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\ProcessApprover;
use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Trunc;

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
        if($leaveRequest->approvalStatus->status == 'Created' && $user->id == $leaveRequest->user_id){            
            return $user->can('update_leave::request');
        }
        return false;
        
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {        
        if($leaveRequest->approvalStatus->status == 'Created' && $user->id == $leaveRequest->user_id){            
            return $user->can('delete_leave::request');
        }
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {        
        return $user->can('delete_any_leave::request');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('force_delete_leave::request');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {        
        return $user->can('force_delete_any_leave::request');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, LeaveRequest $leaveRequest): bool
    {
        if($user->hasRole('super_admin')) return true;

        if($leaveRequest->approvalStatus->status == 'Created' && $user->id == $leaveRequest->user_id){                        
            return $user->can('restore_leave::request');
        }

        return false;
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
        if($leaveRequest->isSubmitted() && !$leaveRequest->isApprovalCompleted() && !$leaveRequest->isDiscarded()){
            $nextStep = $leaveRequest->nextApprovalStep();
            $approval = $leaveRequest->user->approvers->where('model_type', get_class($leaveRequest))->where('role_id', $nextStep->role_id)->first();
            if($approval && $approval->approver_id == $user->id){
                return $leaveRequest->canBeApprovedBy($user);
            }            
        }
        
        return false;
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {     
        if($leaveRequest->isSubmitted() && !$leaveRequest->isApprovalCompleted() && !$leaveRequest->isRejected() && !$leaveRequest->isDiscarded()){
            $nextStep = $leaveRequest->nextApprovalStep();
            $approval = $leaveRequest->user->approvers->where('model_type', get_class($leaveRequest))->where('role_id', $nextStep->role_id)->first();
            if($approval && $approval->approver_id == $user->id){
                return $leaveRequest->canBeApprovedBy($user);
            }               
        }
        
        return false;
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, LeaveRequest $leaveRequest): bool
    {                     
        if($leaveRequest->isRejected() && !$leaveRequest->isDiscarded()){
            $nextStep = $leaveRequest->nextApprovalStep();
            $approval = $leaveRequest->user->approvers->where('model_type', get_class($leaveRequest))->where('role_id', $nextStep->role_id)->first();
            if($leaveRequest->user_id == $user->id || ($approval && $approval->approver_id == $user->id)){
                return true;
            }                  
        }       

        return false;
    }
    
}

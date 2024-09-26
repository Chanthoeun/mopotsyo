<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SwitchWorkDay;
use App\Settings\SettingOptions;
use Illuminate\Auth\Access\HandlesAuthorization;

class SwitchWorkDayPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {        
        if(app(SettingOptions::class)->allow_switch_day_work == true){
            return $user->can('view_any_switch::work::day');
        } 
        return false;       
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        if(app(SettingOptions::class)->allow_switch_day_work == true){
            return $user->can('view_switch::work::day');
        } 
        return false;         
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if(app(SettingOptions::class)->allow_switch_day_work == true && $user->contract && strtolower($user->contract->contractType->abbr) == 'ptc'){
            return $user->can('create_switch::work::day');
        } 
        return false;          
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

    /**
     * Determine whether the user can approve.
     */
    public function approve(User $user, SwitchWorkDay $switchWorkDay): bool
    {
        $nextStep = $switchWorkDay->nextApprovalStep();
        if($nextStep){
            $getApprover = $switchWorkDay->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($switchWorkDay->isSubmitted() &&
                    !$switchWorkDay->isApprovalCompleted() &&
                    !$switchWorkDay->isDiscarded() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($switchWorkDay->canBeApprovedBy($user) && $switchWorkDay->isSubmitted() &&
                    !$switchWorkDay->isApprovalCompleted() &&
                    !$switchWorkDay->isDiscarded()) return true;
                }            
            }
        }    
        // dump($nextStep);
        
        return false;
    }
    /**
     * Determine whether the user can reject.
     */
    public function reject(User $user, SwitchWorkDay $switchWorkDay): bool
    {           
        $nextStep = $switchWorkDay->nextApprovalStep();
        if($nextStep){
            $getApprover = $switchWorkDay->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($switchWorkDay->isSubmitted() &&
                    !$switchWorkDay->isApprovalCompleted() &&
                    !$switchWorkDay->isRejected() &&
                    !$switchWorkDay->isDiscarded() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($switchWorkDay->canBeApprovedBy($user) && $switchWorkDay->isSubmitted() &&
                    !$switchWorkDay->isApprovalCompleted() &&
                    !$switchWorkDay->isRejected() &&
                    !$switchWorkDay->isDiscarded()) return true;
                }            
            }
        }else{
            if($switchWorkDay->canBeApprovedBy($user) && $switchWorkDay->isSubmitted() &&
                !$switchWorkDay->isApprovalCompleted() &&
                !$switchWorkDay->isRejected() &&
                !$switchWorkDay->isDiscarded()) return true;
        }
        
        return false;
    }
    /**
     * Determine whether the user can discard.
     */
    public function discard(User $user, SwitchWorkDay $switchWorkDay): bool
    {                           
        // dd($leaveRequest->nextApprovalStep()->role_id);
        $nextStep = $switchWorkDay->nextApprovalStep();
        if($nextStep){
            $getApprover = $switchWorkDay->processApprovers()->where('step_id', $nextStep->id)->where('role_id', $nextStep->role_id)->first();
            if($getApprover){
                if($getApprover->approver){
                    if($switchWorkDay->isRejected() && $user->id == $getApprover->approver->id) return true;
                }else{
                    if($switchWorkDay->canBeApprovedBy($user) && $switchWorkDay->isRejected()) return true;
                }            
            }
        }else{
            if($switchWorkDay->canBeApprovedBy($user) && $switchWorkDay->isRejected()) return true;
        }   
                
        return false;
    }
}

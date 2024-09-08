<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\Department;
use App\Models\LeaveRequestRule;
use App\Models\ProcessApprover;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = Auth::user();
        $entitlement = $user->entitlements()->where('leave_type_id', $data['leave_type_id'])->where('is_active', true)->whereDate('end_date', '>=', now())->first();   
        if($entitlement){
            return $entitlement->leaveRequests()->create($data);
        }     

        return static::getModel()::create($data);
    }


    protected function afterCreate(): void
    {
        $rules = LeaveRequestRule::where('leave_type_id', $this->record->leave_type_id)->get();
        if(count($rules) > 0){
            foreach($rules as $rule){
                if($this->record->days >= $rule->from_amount && $this->record->days <= $rule->to_amount){
                    $roles = $rule->roles;
                }else if($this->record->days >= $rule->from_amount && empty($rule->to_amount)){
                    $roles = $rule->roles;
                }           
            } 

            $this->record->approvalStatus()->update([
               'steps' => $this->record->approvalFlowSteps()->whereIn('role_id', $roles)->map(function ($item) {
                    $user = $this->record->approvalStatus->creator; 
                    
                    if($user->supervisor->hasRole($item->role_id)){
                        ProcessApprover::create([
                            'step_id'           => $item->id,
                            'modelable_type'    => get_class($this->record),
                            'modelable_id'      => $this->record->id,
                            'role_id'           => $item->role_id,
                            'approver_id'       => $user->supervisor->id
                        ]);                        
                    }else{
                        if(!empty($user->department_head) && $user->department_head->hasRole($item->role_id)){
                            ProcessApprover::create([
                                'step_id'           => $item->id,
                                'modelable_type'    => get_class($this->record),
                                'modelable_id'      => $this->record->id,
                                'role_id'           => $item->role_id,
                                'approver_id'       => $user->department_head->id
                            ]);                            
                        }else{
                            ProcessApprover::create([
                                'step_id'           => $item->id,
                                'modelable_type'    => get_class($this->record),
                                'modelable_id'      => $this->record->id,
                                'role_id'           => $item->role_id
                            ]);                            
                        }
                    }
                       
                    return $item->toApprovalStatusArray();
                })->toArray()
            ]);
        }               
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.leave_request')]))
            ->body(__('msg.body.created', ['name' => __('model.leave_request')]));
    }
}

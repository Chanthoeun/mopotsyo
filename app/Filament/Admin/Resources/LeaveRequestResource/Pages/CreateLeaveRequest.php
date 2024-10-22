<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
    
        return $data;
    }

    protected function afterCreate(): void
    {
        $approvers = $this->record->user->approvers->where('model_type', LeaveRequest::class);
        if($approvers->count() == 1){
            $approvers = $approvers->pluck('role_id');
        }else{
            $leaveType = $this->record->leaveType;
            if(!empty($leaveType->rules)){
                foreach($leaveType->rules as $rule){
                    if($this->record->days >= $rule['from_amount'] && $this->record->days <= $rule['to_amount']){
                        $roles = $rule['roles'];
                    }else if($this->record->days >= $rule['from_amount'] && empty($rule['to_amount'])){
                        $roles = $rule['roles'];
                    }           
                }

                $approvers = $this->record->user->approvers->where('model_type', LeaveRequest::class)->whereIn('role_id', $roles);
                if($approvers->count() == 0){
                    $approvers[] = $this->record->user->approvers->where('model_type', LeaveRequest::class)->first()->role_id;
                }else{
                    $approvers = $approvers->pluck('role_id');
                }
            }else{
                $approvers = $approvers->pluck('role_id');
            }
        }
        
        
        $steps = $this->record->approvalFlowSteps()->whereIn('role_id', $approvers->toArray())->map(function ($item) {                    
            return $item->toApprovalStatusArray();
        })->toArray();
        
        $this->record->approvalStatus()->update([
            'steps' => array_values($steps)
         ]);    
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.leave_request')]))
            ->body(__('msg.body.created', ['name' => __('model.leave_request')]));
    }
}

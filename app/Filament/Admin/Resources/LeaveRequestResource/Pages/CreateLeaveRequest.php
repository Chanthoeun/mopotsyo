<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\LeaveRequestRule;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
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
        $entitlement = $user->entitlements->where('leave_type_id', $data['leave_type_id'])->first();        
        return $entitlement->leaveRequests()->create($data);
    }


    protected function afterCreate(): void
    {
        $rules = LeaveRequestRule::where('leave_type_id', $this->record->leave_type_id)->get();
        if($rules){
            foreach($rules as $rule){
                if($this->record->days >= $rule->from_amount && $this->record->days <= $rule->to_amount){
                    $roles = $rule->roles;
                }else if($this->record->days >= $rule->from_amount && empty($rule->to_amount)){
                    $roles = $rule->roles;
                }           
            }       
    
            $this->record->approvalStatus()->update([
               'steps' => $this->record->approvalFlowSteps()->whereIn('role_id', $roles)->map(function ($item) {
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

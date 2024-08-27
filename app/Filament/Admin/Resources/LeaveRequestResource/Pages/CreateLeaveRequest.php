<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::with('entitlements')->find($data['user_id']);
        $entitlement = $user->entitlements->where('leave_type_id', $data['leave_type_id'])->first();        
        return $entitlement->leaveRequests()->create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.leave_request')]))
            ->body(__('msg.body.created', ['name' => __('model.leave_request')]));
    }
}

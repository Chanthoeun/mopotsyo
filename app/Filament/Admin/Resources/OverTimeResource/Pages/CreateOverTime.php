<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\OverTime;
use App\Models\ProcessApprover;
use App\Settings\SettingOptions;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOverTime extends CreateRecord
{
    protected static string $resource = OverTimeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['expiry_date'] = now()->addDays(app(SettingOptions::class)->overtime_expiry);
        $data['unused']      = true;   
        $data['user_id']     = Auth::id();
    
        return $data;
    }

    protected function afterCreate(): void
    {
        $approvers = $this->record->user->approvers->where('model_type', OverTime::class)->pluck('role_id');        
        
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
            ->title(__('msg.label.created', ['label' => __('model.overtime')]))
            ->body(__('msg.body.created', ['name' => __('model.overtime')]));
    }
}

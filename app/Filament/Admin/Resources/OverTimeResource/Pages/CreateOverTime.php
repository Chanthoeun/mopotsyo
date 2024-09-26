<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
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
        // create process approval
        createProcessApprover($this->record);                   
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.overtime')]))
            ->body(__('msg.body.created', ['name' => __('model.overtime')]));
    }
}

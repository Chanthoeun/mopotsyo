<?php

namespace App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;

use App\Filament\Admin\Resources\SwitchWorkDayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSwitchWorkDay extends CreateRecord
{
    protected static string $resource = SwitchWorkDayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id']     = Auth::id();
    
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // create process approval
        createProcessApprover($this->record);                   
    }
}

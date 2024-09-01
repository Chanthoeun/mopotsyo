<?php

namespace App\Filament\Admin\Resources\LeaveTypeResource\Pages;

use App\Filament\Admin\Resources\LeaveTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveType extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = LeaveTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),            
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\LeaveTypeResource\Pages;

use App\Filament\Admin\Resources\LeaveTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveType extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

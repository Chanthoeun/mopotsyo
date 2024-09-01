<?php

namespace App\Filament\Admin\Resources\LeaveCarryForwardResource\Pages;

use App\Filament\Admin\Resources\LeaveCarryForwardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveCarryForward extends EditRecord
{
    protected static string $resource = LeaveCarryForwardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

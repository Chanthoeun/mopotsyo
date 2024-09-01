<?php

namespace App\Filament\Admin\Resources\LeaveEntitlementResource\Pages;

use App\Filament\Admin\Resources\LeaveEntitlementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveEntitlement extends EditRecord
{
    protected static string $resource = LeaveEntitlementResource::class;

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

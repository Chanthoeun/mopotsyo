<?php

namespace App\Filament\Admin\Resources\LeaveEntitlementResource\Pages;

use App\Filament\Admin\Resources\LeaveEntitlementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveEntitlement extends CreateRecord
{
    protected static string $resource = LeaveEntitlementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Admin\Resources\LeaveEntitlementResource\Pages;

use App\Filament\Admin\Resources\LeaveEntitlementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveEntitlements extends ListRecords
{
    protected static string $resource = LeaveEntitlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.entitlement')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }
}

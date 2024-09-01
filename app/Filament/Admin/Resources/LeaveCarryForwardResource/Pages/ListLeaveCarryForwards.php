<?php

namespace App\Filament\Admin\Resources\LeaveCarryForwardResource\Pages;

use App\Filament\Admin\Resources\LeaveCarryForwardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveCarryForwards extends ListRecords
{
    protected static string $resource = LeaveCarryForwardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.carry_forward')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }
}

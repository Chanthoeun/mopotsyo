<?php

namespace App\Filament\Admin\Resources\LeaveTypeResource\Pages;

use App\Filament\Admin\Resources\LeaveTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveTypes extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.leave_type')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

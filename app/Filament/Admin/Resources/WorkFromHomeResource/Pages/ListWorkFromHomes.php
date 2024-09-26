<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkFromHomes extends ListRecords
{
    protected static string $resource = WorkFromHomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.request', ['label' => __('model.work_from_home')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }
}

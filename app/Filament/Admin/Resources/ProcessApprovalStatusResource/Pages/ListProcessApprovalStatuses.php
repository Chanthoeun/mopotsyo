<?php

namespace App\Filament\Admin\Resources\ProcessApprovalStatusResource\Pages;

use App\Filament\Admin\Resources\ProcessApprovalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessApprovalStatuses extends ListRecords
{
    protected static string $resource = ProcessApprovalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}

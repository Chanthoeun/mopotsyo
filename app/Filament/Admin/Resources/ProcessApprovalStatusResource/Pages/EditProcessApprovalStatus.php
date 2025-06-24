<?php

namespace App\Filament\Admin\Resources\ProcessApprovalStatusResource\Pages;

use App\Filament\Admin\Resources\ProcessApprovalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcessApprovalStatus extends EditRecord
{
    protected static string $resource = ProcessApprovalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

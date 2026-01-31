<?php

namespace App\Filament\Admin\Resources\LogResource\Pages;

use App\Filament\Admin\Resources\LogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLog extends ViewRecord
{
    protected static string $resource = LogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

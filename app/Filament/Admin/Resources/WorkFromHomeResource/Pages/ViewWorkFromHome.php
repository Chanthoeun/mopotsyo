<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkFromHome extends ViewRecord
{
    protected static string $resource = WorkFromHomeResource::class;

    public function getSubheading(): ?string
    {
        return __('field.status') . ': ' . $this->record->status->getLabel();
    }

    protected function getHeaderActions(): array
    {
        return \App\Actions\ApprovalActions::makePageActions([], [
            Actions\EditAction::make(),
        ]);
    }
}

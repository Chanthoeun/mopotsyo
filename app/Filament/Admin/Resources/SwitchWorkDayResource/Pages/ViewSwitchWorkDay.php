<?php

namespace App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;

use App\Filament\Admin\Resources\SwitchWorkDayResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewSwitchWorkDay extends ViewRecord
{

    protected static string $resource = SwitchWorkDayResource::class;

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

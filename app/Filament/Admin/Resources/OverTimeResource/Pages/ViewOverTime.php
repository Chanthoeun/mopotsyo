<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewOverTime extends ViewRecord
{

    protected static string $resource = OverTimeResource::class;

    public function getSubheading(): ?string
    {
        return __('field.status') . ': ' . $this->record->status->getLabel();
    }

    protected function getHeaderActions(): array
    {
        return \App\Actions\ApprovalActions::makePageActions([], [
            Actions\EditAction::make()
                ->visible(fn(\App\Models\OverTime $record) => $record->user_id == \Illuminate\Support\Facades\Auth::id() && $record->status === \App\Enums\Status::CREATED),
        ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewLeaveRequest extends ViewRecord
{

    protected static string $resource = LeaveRequestResource::class;

    public function getSubheading(): ?string
    {
        $subheading = __('field.status') . ': ' . $this->record->status->getLabel();

        if ($this->record->user_id !== Auth::id()) {
            $subheading .= ' | ' . __('field.requested_by') . ': ' . $this->record->user->full_name;
        }

        return $subheading;
    }

    protected function getHeaderActions(): array
    {
        return \App\Actions\ApprovalActions::makePageActions([], [
            Actions\EditAction::make()
                ->visible(fn(\App\Models\LeaveRequest $record) => $record->user_id == \Illuminate\Support\Facades\Auth::id() && $record->status === \App\Enums\Status::CREATED),
        ]);
    }
}

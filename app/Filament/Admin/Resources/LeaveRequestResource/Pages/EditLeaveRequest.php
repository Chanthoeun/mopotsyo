<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (!empty($data['requestDates'])) {
            $userId = $this->record->user_id;
            $currentRequestId = $this->record->id;

            foreach ($data['requestDates'] as $requestDate) {
                if (empty($requestDate['date'])) {
                    continue;
                }

                // Check if this date already exists in another leave request (not this one)
                $existingRequest = \App\Models\RequestDate::where('date', $requestDate['date'])
                    ->whereHasMorph('requestdateable', [LeaveRequest::class], function ($query) use ($userId, $currentRequestId) {
                        $query->where('user_id', $userId)
                            ->where('id', '!=', $currentRequestId)
                            ->whereIn('status', ['approved', 'pending', 'created']);
                    })
                    ->first();

                if ($existingRequest) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('validation.duplicate_leave_date'))
                        ->body(__('validation.duplicate_leave_date_body', [
                            'date' => \Carbon\Carbon::parse($requestDate['date'])->format('Y-m-d')
                        ]))
                        ->send();

                    $this->halt();
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label(__('btn.submit'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (LeaveRequest $record) {
                    $record->submitToApproval();
                    $this->redirect($this->getResource()::getUrl('index'));
                    \Filament\Notifications\Notification::make()
                        ->title(__('msg.body.submitted', ['label' => __('model.leave_request')]))
                        ->success()
                        ->send();
                })
                ->visible(fn(LeaveRequest $record) => $record->status === \App\Enums\Status::CREATED),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

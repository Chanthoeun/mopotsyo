<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\OverTime;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOverTime extends EditRecord
{
    protected static string $resource = OverTimeResource::class;

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

                // Check if this date already exists in another overtime request (not this one)
                $existingRequest = \App\Models\RequestDate::where('date', $requestDate['date'])
                    ->whereHasMorph('requestdateable', [OverTime::class], function ($query) use ($userId, $currentRequestId) {
                        $query->where('user_id', $userId)
                            ->where('id', '!=', $currentRequestId)
                            ->whereIn('status', ['approved', 'pending', 'created']);
                    })
                    ->first();

                if ($existingRequest) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title(__('validation.duplicate_request_date'))
                        ->body(__('validation.duplicate_request_date_body', [
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
                ->action(function (OverTime $record) {
                    $record->submitToApproval();
                    $this->redirect($this->getResource()::getUrl('index'));
                    \Filament\Notifications\Notification::make()
                        ->title(__('msg.body.submitted', ['label' => __('model.overtime')]))
                        ->success()
                        ->send();
                })
                ->visible(fn(OverTime $record) => $record->status === \App\Enums\Status::CREATED),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

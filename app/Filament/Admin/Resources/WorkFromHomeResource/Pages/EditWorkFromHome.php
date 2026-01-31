<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
use App\Models\WorkFromHome;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkFromHome extends EditRecord
{
    protected static string $resource = WorkFromHomeResource::class;

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

                // Check if this date already exists in another WFH request (not this one)
                $existingRequest = \App\Models\RequestDate::where('date', $requestDate['date'])
                    ->whereHasMorph('requestdateable', [WorkFromHome::class], function ($query) use ($userId, $currentRequestId) {
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
                ->action(function (WorkFromHome $record) {
                    $record->submitToApproval();
                    $this->redirect($this->getResource()::getUrl('index'));
                    \Filament\Notifications\Notification::make()
                        ->title(__('msg.body.submitted', ['label' => __('model.work_from_home')]))
                        ->success()
                        ->send();
                })
                ->visible(fn(WorkFromHome $record) => $record->status === \App\Enums\Status::CREATED),
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

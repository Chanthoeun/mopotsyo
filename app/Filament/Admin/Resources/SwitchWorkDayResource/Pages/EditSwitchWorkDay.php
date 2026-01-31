<?php

namespace App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;

use App\Filament\Admin\Resources\SwitchWorkDayResource;
use App\Models\SwitchWorkDay;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSwitchWorkDay extends EditRecord
{
    protected static string $resource = SwitchWorkDayResource::class;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (!empty($data['from_date']) && !empty($data['to_date'])) {
            $userId = $this->record->user_id;
            $currentRequestId = $this->record->id;

            // Check if there's an overlapping switch work day request (not this one)
            $existingRequest = SwitchWorkDay::where('user_id', $userId)
                ->where('id', '!=', $currentRequestId)
                ->whereIn('status', ['approved', 'pending', 'created'])
                ->where(function ($query) use ($data) {
                    $query->whereBetween('from_date', [$data['from_date'], $data['to_date']])
                        ->orWhereBetween('to_date', [$data['from_date'], $data['to_date']])
                        ->orWhere(function ($q) use ($data) {
                            $q->where('from_date', '<=', $data['from_date'])
                                ->where('to_date', '>=', $data['to_date']);
                        });
                })
                ->first();

            if ($existingRequest) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title(__('validation.duplicate_switch_work_day'))
                    ->body(__('validation.duplicate_switch_work_day_body', [
                        'from' => \Carbon\Carbon::parse($existingRequest->from_date)->format('Y-m-d'),
                        'to' => \Carbon\Carbon::parse($existingRequest->to_date)->format('Y-m-d')
                    ]))
                    ->send();

                $this->halt();
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
                ->action(function (SwitchWorkDay $record) {
                    $record->submitToApproval();
                    $this->redirect($this->getResource()::getUrl('index'));
                    \Filament\Notifications\Notification::make()
                        ->title(__('msg.body.submitted', ['label' => __('model.switch_work_day')]))
                        ->success()
                        ->send();
                })
                ->visible(fn(SwitchWorkDay $record) => $record->status === \App\Enums\Status::CREATED),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

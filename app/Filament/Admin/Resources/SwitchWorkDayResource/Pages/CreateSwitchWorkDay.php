<?php

namespace App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;

use App\Filament\Admin\Resources\SwitchWorkDayResource;
use App\Models\SwitchWorkDay;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreateSwitchWorkDay extends CreateRecord
{
    protected static string $resource = SwitchWorkDayResource::class;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (!empty($data['from_date']) && !empty($data['to_date'])) {
            $userId = Auth::id();

            // Check if there's an overlapping switch work day request
            $existingRequest = SwitchWorkDay::where('user_id', $userId)
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

    protected function beforeCreate(): void
    {
        $userId = $this->data['user_id'] ?? Auth::id();
        $user = User::find($userId);

        $model = new ($this->getModel());
        if (!$model->validateContractConfiguration($user)) {
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = \App\Enums\Status::CREATED;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // $this->record->submitToApproval();
    }
}

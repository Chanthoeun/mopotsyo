<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreateWorkFromHome extends CreateRecord
{
    protected static string $resource = WorkFromHomeResource::class;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (!empty($data['requestDates'])) {
            $userId = Auth::id();

            foreach ($data['requestDates'] as $requestDate) {
                if (empty($requestDate['date'])) {
                    continue;
                }

                // Check if this date already exists in another WFH request
                $existingRequest = \App\Models\RequestDate::where('date', $requestDate['date'])
                    ->whereHasMorph('requestdateable', [WorkFromHome::class], function ($query) use ($userId) {
                        $query->where('user_id', $userId)
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

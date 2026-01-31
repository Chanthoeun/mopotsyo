<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\OverTime;
use App\Models\ProcessApprover;
use App\Settings\SettingOptions;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreateOverTime extends CreateRecord
{
    protected static string $resource = OverTimeResource::class;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (!empty($data['requestDates'])) {
            $userId = Auth::id();

            foreach ($data['requestDates'] as $requestDate) {
                if (empty($requestDate['date'])) {
                    continue;
                }

                // Check if this date already exists in another overtime request
                $existingRequest = \App\Models\RequestDate::where('date', $requestDate['date'])
                    ->whereHasMorph('requestdateable', [OverTime::class], function ($query) use ($userId) {
                        $query->where('user_id', $userId)
                            ->whereIn('status', ['approved', 'pending', 'created']);
                    })
                    ->first();

                if ($existingRequest) {
                    Notification::make()
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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['expiry_date'] = now()->addDays(app(SettingOptions::class)->overtime_expiry);
        $data['unused'] = true;
        $data['user_id'] = Auth::id();
        $data['status'] = \App\Enums\Status::CREATED;

        return $data;
    }

    protected function afterCreate(): void
    {
        // $this->record->submitToApproval();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.overtime')]))
            ->body(__('msg.body.created', ['name' => __('model.overtime')]));
    }
}

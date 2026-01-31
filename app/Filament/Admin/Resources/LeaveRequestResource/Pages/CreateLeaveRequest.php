<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\Department;
use App\Models\LeaveCarryForward;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function beforeValidate(): void
    {
        $data = $this->form->getState();

        if (!empty($data['requestDates'])) {
            $userId = Auth::id();

            foreach ($data['requestDates'] as $requestDate) {
                if (empty($requestDate['date'])) {
                    continue;
                }

                // Check if this date already exists in another leave request
                $existingRequest = \App\Models\RequestDate::where('date', $requestDate['date'])
                    ->whereHasMorph('requestdateable', [LeaveRequest::class], function ($query) use ($userId) {
                        $query->where('user_id', $userId)
                            ->whereIn('status', ['approved', 'pending', 'created']);
                    })
                    ->first();

                if ($existingRequest) {
                    Notification::make()
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
        $data['user_id'] = Auth::id();
        $data['status'] = \App\Enums\Status::CREATED;

        return $data;
    }

    protected function afterCreate(): void
    {
        // $this->record->submitToApproval();

        // check Carry Forward add add leave to carry forward
        foreach ($this->record->requestDates as $requestDate) {
            if ($requestDate->leave_carry_forward_id) {
                continue;
            }

            // check if leave request type is Annual Leave
            $leaveRequest = $this->record;
            $leaveType = LeaveType::where('name', 'like', '%Annual%')->first();

            if (!$leaveType || $leaveRequest->leave_type_id != $leaveType->id) {
                continue;
            }

            $carryForward = LeaveCarryForward::where('user_id', $this->record->user_id)
                ->whereDate('start_date', '<=', $requestDate->date)
                ->whereDate('end_date', '>=', $requestDate->date)
                ->first();

            if ($carryForward && $carryForward->remaining > 0) {
                $requestDate->leave_carry_forward_id = $carryForward->id;
                $requestDate->save();
            }
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.leave_request')]))
            ->body(__('msg.body.created', ['name' => __('model.leave_request')]));
    }
}

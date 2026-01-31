<?php

namespace App\Filament\Admin\Resources\LeaveCarryForwardResource\Pages;

use App\Filament\Admin\Resources\LeaveCarryForwardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateLeaveCarryForward extends CreateRecord
{
    protected static string $resource = LeaveCarryForwardResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $from_date = $this->record->start_date;
        $to_date = $this->record->end_date;
        $user = $this->record->user;
        $leaves = $user->leaveRequests()->with('requestDates')->where('leave_type_id', 1)
            ->whereHas('requestDates', function ($q) use ($from_date, $to_date) {
                $q->whereBetween('date', [$from_date, $to_date]);
            })->whereIn('status', ['pending', 'approved'])->get();

        $remaining = $this->record->remaining;
        foreach ($leaves as $leave) {
            $requestDates = $leave->requestDates()->whereBetween('date', [$from_date, $to_date])->get();
            foreach ($requestDates as $requestDate) {
                if ($remaining > 0 && $remaining >= $requestDate->day && $requestDate->leave_carry_forward_id == null) {
                    $requestDate->leave_carry_forward_id = $this->record->id;
                    $requestDate->save();

                    $remaining = floatval($remaining - $requestDate->day);
                }
            }
        }
    }
}

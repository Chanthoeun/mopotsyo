<?php

namespace App\Filament\Admin\Resources\TimesheetResource\Pages;

use App\Filament\Admin\Resources\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Auto-generate timesheet name: Employee name - Month Year
        $user = Auth::user();
        $employeeName = $user->name;

        // Get month and year from from_date
        $fromDate = \Carbon\Carbon::parse($data['from_date']);
        $monthYear = $fromDate->format('F Y'); // e.g., "February 2026"

        $data['name'] = $employeeName . ' - ' . $monthYear;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // create process approval                               
    }
}

<?php

namespace App\Filament\Admin\Resources\TimesheetResource\Pages;

use App\Filament\Admin\Resources\TimesheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimesheet extends EditRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-generate timesheet name: Employee name - Month Year
        $user = $this->record->user;
        $employeeName = $user->name;

        // Get month and year from from_date
        $fromDate = \Carbon\Carbon::parse($data['from_date']);
        $monthYear = $fromDate->format('F Y'); // e.g., "February 2026"

        $data['name'] = $employeeName . ' - ' . $monthYear;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

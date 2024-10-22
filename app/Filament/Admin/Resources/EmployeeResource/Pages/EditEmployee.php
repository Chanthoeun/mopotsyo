<?php

namespace App\Filament\Admin\Resources\EmployeeResource\Pages;

use App\Filament\Admin\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use JoseEspinal\RecordNavigation\Traits\HasRecordNavigation;

class EditEmployee extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    use HasRecordNavigation;

    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        $existingActions = [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\LocaleSwitcher::make(),
        ];

        return array_merge($existingActions, $this->getNavigationActions());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

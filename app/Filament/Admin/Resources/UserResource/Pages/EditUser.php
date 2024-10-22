<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use JoseEspinal\RecordNavigation\Traits\HasRecordNavigation;

class EditUser extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    use HasRecordNavigation;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $existingActions = [
            Actions\DeleteAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
        return array_merge($existingActions, $this->getNavigationActions());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Admin\Resources\LocationResource\Pages;

use App\Filament\Admin\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

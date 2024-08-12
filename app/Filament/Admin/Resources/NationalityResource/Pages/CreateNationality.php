<?php

namespace App\Filament\Admin\Resources\NationalityResource\Pages;

use App\Filament\Admin\Resources\NationalityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNationality extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = NationalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),            
        ];
    }
}

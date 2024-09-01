<?php

namespace App\Filament\Admin\Resources\LocationResource\Pages;

use App\Filament\Admin\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = LocationResource::class;

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

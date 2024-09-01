<?php

namespace App\Filament\Admin\Resources\PublicHolidayResource\Pages;

use App\Filament\Admin\Resources\PublicHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePublicHoliday extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = PublicHolidayResource::class;

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

<?php

namespace App\Filament\Admin\Resources\ShiftResource\Pages;

use App\Filament\Admin\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateShift extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ShiftResource::class;

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

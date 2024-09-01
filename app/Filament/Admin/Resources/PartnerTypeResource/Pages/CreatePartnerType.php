<?php

namespace App\Filament\Admin\Resources\PartnerTypeResource\Pages;

use App\Filament\Admin\Resources\PartnerTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePartnerType extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    
    protected static string $resource = PartnerTypeResource::class;

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

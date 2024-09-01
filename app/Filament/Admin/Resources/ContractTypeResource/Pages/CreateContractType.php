<?php

namespace App\Filament\Admin\Resources\ContractTypeResource\Pages;

use App\Filament\Admin\Resources\ContractTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContractType extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ContractTypeResource::class;

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

<?php

namespace App\Filament\Admin\Resources\ContractTypeResource\Pages;

use App\Filament\Admin\Resources\ContractTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContractType extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = ContractTypeResource::class;

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

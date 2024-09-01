<?php

namespace App\Filament\Admin\Resources\PartnerTypeResource\Pages;

use App\Filament\Admin\Resources\PartnerTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartnerType extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    
    protected static string $resource = PartnerTypeResource::class;

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

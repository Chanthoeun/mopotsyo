<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class EditUser extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        
        return [
            Impersonate::make()->record($this->getRecord())
                ->label('Impersonate')
                ->icon('fas-user-secret')
                ->color('info')
                ->button(),
            Actions\DeleteAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

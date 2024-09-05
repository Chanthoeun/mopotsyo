<?php

namespace App\Filament\Admin\Resources\ProcessApprovalRuleResource\Pages;

use App\Filament\Admin\Resources\ProcessApprovalRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcessApprovalRule extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = ProcessApprovalRuleResource::class;

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

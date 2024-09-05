<?php

namespace App\Filament\Admin\Resources\ProcessApprovalRuleResource\Pages;

use App\Filament\Admin\Resources\ProcessApprovalRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProcessApprovalRule extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = ProcessApprovalRuleResource::class;

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

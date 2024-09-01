<?php

namespace App\Filament\Admin\Resources\LeaveRequestRuleResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequestRule extends EditRecord
{
    use EditRecord\Concerns\Translatable;

    protected static string $resource = LeaveRequestRuleResource::class;

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

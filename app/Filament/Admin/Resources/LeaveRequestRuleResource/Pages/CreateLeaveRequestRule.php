<?php

namespace App\Filament\Admin\Resources\LeaveRequestRuleResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequestRule extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = LeaveRequestRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
    
        return $data;
    }

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

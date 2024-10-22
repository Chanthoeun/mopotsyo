<?php

namespace App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;

use App\Filament\Admin\Resources\SwitchWorkDayResource;
use App\Models\SwitchWorkDay;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSwitchWorkDay extends CreateRecord
{
    protected static string $resource = SwitchWorkDayResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id']     = Auth::id();
    
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $approvers = $this->record->user->approvers->where('model_type', SwitchWorkDay::class)->pluck('role_id');        
        
        $steps = $this->record->approvalFlowSteps()->whereIn('role_id', $approvers->toArray())->map(function ($item) {                    
            return $item->toApprovalStatusArray();
        })->toArray();
        
        $this->record->approvalStatus()->update([
            'steps' => array_values($steps)
         ]);                      
    }
}

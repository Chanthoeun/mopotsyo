<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkFromHome extends CreateRecord
{
    protected static string $resource = WorkFromHomeResource::class;

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
        $approvers = $this->record->user->approvers->where('model_type', WorkFromHome::class);
        if($approvers->count() == 1){
            $approvers = $approvers->pluck('role_id');
        }else{
            if(app(SettingOptions::class)->work_from_home_rules){
                foreach(app(SettingOptions::class)->work_from_home_rules as $rule){ 
                    if($this->record->days >= $rule['from_amount'] && $this->record->days <= $rule['to_amount']){
                        $roles = $rule['roles'];
                    }else if($this->record->days >= $rule['from_amount'] && empty($rule['to_amount'])){
                        $roles = $rule['roles'];
                    }
                }
                $approvers = $this->record->user->approvers->where('model_type', WorkFromHome::class)->whereIn('role_id', $roles);
                if($approvers->count() == 0){
                    $approvers[] = $this->record->user->approvers->where('model_type', WorkFromHome::class)->first()->role_id;
                }else{
                    $approvers = $approvers->pluck('role_id');
                }
            }else{
                $approvers = $approvers->pluck('role_id');
            }
        }
        
        $steps = $this->record->approvalFlowSteps()->whereIn('role_id', $approvers->toArray())->map(function ($item) {                    
            return $item->toApprovalStatusArray();
        })->toArray();
        
        $this->record->approvalStatus()->update([
            'steps' => array_values($steps)
        ]);                      
    }
}

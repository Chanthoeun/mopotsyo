<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
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
        // create process approval
        if(app(SettingOptions::class)->work_from_home_rules){
            foreach(app(SettingOptions::class)->work_from_home_rules as $rule){ 
                if($this->record->days >= $rule['from_amount'] && $this->record->days <= $rule['to_amount']){
                    $roles = $rule['roles'];
                }else if($this->record->days >= $rule['from_amount'] && empty($rule['to_amount'])){
                    $roles = $rule['roles'];
                }
            }

            $this->record->approvalStatus()->update([
                'steps' => $this->record->approvalFlowSteps()->whereIn('role_id', $roles)->map(function ($item) {                    
                     // create process approver
                     createProcessApprover($this->record, $item);
                        
                     return $item->toApprovalStatusArray();
                 })->toArray()
             ]);
        }else{
            createProcessApprover($this->record);
        }                           
    }
}

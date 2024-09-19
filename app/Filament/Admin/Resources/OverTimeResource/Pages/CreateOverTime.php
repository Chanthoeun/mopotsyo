<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\ProcessApprover;
use App\Settings\SettingOptions;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateOverTime extends CreateRecord
{
    protected static string $resource = OverTimeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['expiry_date'] = now()->addDays(app(SettingOptions::class)->overtime_expiry);
        $data['unused']      = true;   
        $data['user_id']     = auth()->id();
    
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record->approvalStatus->creator;         
        foreach($this->record->approvalFlowSteps() as $step){
            if($user->supervisor->hasRole($step->role_id)){
                ProcessApprover::create([
                    'step_id'           => $step->id,
                    'modelable_type'    => get_class($this->record),
                    'modelable_id'      => $this->record->id,
                    'role_id'           => $step->role_id,
                    'approver_id'       => $user->supervisor->id
                ]);                        
            }else if(!empty($user->department_head) && $user->department_head->hasRole($step->role_id)){
                ProcessApprover::create([
                    'step_id'           => $step->id,
                    'modelable_type'    => get_class($this->record),
                    'modelable_id'      => $this->record->id,
                    'role_id'           => $step->role_id,
                    'approver_id'       => $user->department_head->id
                ]);                            
            }else{
                ProcessApprover::create([
                    'step_id'           => $step->id,
                    'modelable_type'    => get_class($this->record),
                    'modelable_id'      => $this->record->id,
                    'role_id'           => $step->role_id
                ]);                            
            } 
        }                    
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('msg.label.created', ['label' => __('model.overtime')]))
            ->body(__('msg.body.created', ['name' => __('model.overtime')]));
    }
}

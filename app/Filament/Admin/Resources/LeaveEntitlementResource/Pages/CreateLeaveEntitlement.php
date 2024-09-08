<?php

namespace App\Filament\Admin\Resources\LeaveEntitlementResource\Pages;

use App\Filament\Admin\Resources\LeaveEntitlementResource;
use App\Models\LeaveType;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLeaveEntitlement extends CreateRecord
{
    protected static string $resource = LeaveEntitlementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::with('employee.contracts.contractType')->find($data['user_id']);
        // single leave type
        if(!empty($data['leave_type_id'] && !empty($data['balance']))){            
            $entitlement = $user->entitlements()->where('leave_type_id', $data['leave_type_id'])->where('is_active', true)->whereDate('start_date', $data['start_date'])->whereDate('end_date', $data['end_date'])->first();
            if(empty($entitlement)){
                // disable all other entitlements
                $user->entitlements()->where('leave_type_id', $data['leave_type_id'])->update(['is_active' => false]);

                // create new entitlement for this leave type
                return static::getModel()::create([
                    'user_id' => $user->id,
                    'leave_type_id' => $data['leave_type_id'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'balance' => $data['balance'],
                ]);
            } 
            
        }

        // multiple leave type  
        $contract = $user->employee->contracts->where('is_active', true)->first();      
        $leaveTypes = LeaveType::query();
        $leaveTypes->whereIn('id', $contract->contractType->leave_types);
        $leaveTypes->where('balance', '>', 0);
        $leaveTypes->where($user->employee->gender->value, true);        

        foreach($leaveTypes->get() as $leaveType){
            if(!empty($leaveType->balance_increment_amount) && !empty($leaveType->balance_increment_period)){
                $balance = getEntitlementBalance($user->employee->join_date, $leaveType);
            }else{
                $balance = $leaveType->balance;
            } 

            $entitlement = $user->entitlements()->where('leave_type_id', $leaveType->id)->where('is_active', true)->whereDate('start_date', $data['start_date'])->whereDate('end_date', $data['end_date'])->first();            
            if(empty($entitlement)){
                // disable all other entitlements
                $user->entitlements()->where('leave_type_id', $leaveType->id)->update(['is_active' => false]);

                // create new entitlement for this leave type
                $newEntitlement = $user->entitlements()->create([   
                    'leave_type_id' => $leaveType->id,             
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'balance' => $balance,
                ]); 
            } 
        }

        return $newEntitlement;
    }
}

<?php

namespace App\Filament\Admin\Resources\LeaveEntitlementResource\Pages;

use App\Filament\Admin\Resources\LeaveEntitlementResource;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListLeaveEntitlements extends ListRecords
{
    protected static string $resource = LeaveEntitlementResource::class;    

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.entitlement')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\Action::make('generate')
                ->label(__('btn.label.generate', ['label' => __('model.entitlement')]))
                ->color('primary')
                ->icon('fas-rotate')
                ->requiresConfirmation()
                ->modalHeading(__('btn.label.generate', ['label' => __('model.entitlement')]))
                ->modalDescription(__('btn.msg.generate', ['name' => __('field.all') .' '. __('model.employees')]))
                ->modalIcon('fas-rotate')
                ->visible(fn () => Auth::user()->can('create', LeaveEntitlement::class))
                ->action(function(){
                    $users = User::with(['employee.contracts.contractType', 'entitlements'])->whereHas('employee', function(Builder $q) {
                        $q->whereNull('resign_date');
                        $q->whereHas('contracts', function(Builder $query) {
                            $query->where('is_active', true);
                            $query->whereHas('contractType', function(Builder $query) {
                                $query->where('allow_leave_request', true);
                            });
                        });
                    })->get();                    

                    foreach($users as $user){
                        if($user->isNotBanned()){
                            $contract = $user->employee->contracts->where('is_active', true)->first();
                            $startDate = Carbon::createFromDate(now()->year, $user->employee->join_date->month, $user->employee->join_date->day);
                            $endDate = Carbon::parse($startDate)->addYear()->subDay();
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

                                $entitlement = $user->entitlements()->where('leave_type_id', $leaveType->id)->where('is_active', true)->whereDate('start_date', $startDate)->whereDate('end_date', $endDate)->first();
                                if(empty($entitlement)){
                                    // disable all other entitlements
                                    $user->entitlements()->where('leave_type_id', $leaveType->id)->update(['is_active' => false]);

                                    // create new entitlement for this leave type
                                    $user->entitlements()->create([   
                                        'leave_type_id' => $leaveType->id,             
                                        'start_date' => $startDate,
                                        'end_date' => $endDate,
                                        'balance' => $balance,
                                    ]); 
                                }                                                
                            }
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title(__('msg.label.success', ['label' => __('btn.generate')]))
                        ->body(__('msg.body.success', ['name' => __('model.entitlement'), 'action' => __('action.generated')]))
                        ->send();
                }),
                
        ];
    }

    public function getTabs(): array
    {   
        $tabs = array();
        $tabs['all']    = Tab::make(__('field.all'));

        foreach(LeaveType::wherehas('entitlements')->get() as $type){
            $tabs[$type->name] = Tab::make($type->name)
                                    ->modifyQueryUsing(fn (Builder $query) => $query->where('leave_type_id', $type->id));
        }

        

        return $tabs;
    }
}

<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Filament\Admin\Resources\PurchaseRequestResource;
use App\Filament\Admin\Resources\SwitchWorkDayResource;
use App\Filament\Admin\Resources\WorkFromHomeResource;
use App\Models\LeaveRequest;
use App\Models\OverTime;
use App\Models\PurchaseRequest;
use App\Models\SwitchWorkDay;
use App\Models\User;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use App\Traits\SendNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;

class ProcessApprovalDiscardNotificationListener
{
    use SendNotification;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProcessDiscardedEvent $event): void
    {
        $discarded = $event->approval;
        $approvable = $discarded->approvable;
        if(get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestDiscarded($approvable, $discarded);
        }else if(get_class($approvable) == OverTime::class){
            $this->overtimeDiscarded($approvable, $discarded);
        }else if(get_class($approvable) == SwitchWorkDay::class){
            $this->switchWorkDayDiscarded($approvable, $discarded);
        }else if(get_class($approvable) == WorkFromHome::class){
            $this->workFromHomeDiscarded($approvable, $discarded);
        }else if(get_class($approvable) == PurchaseRequest::class){
            $this->purchaseRequestDiscarded($approvable, $discarded);
        }
    }

    protected function leaveRequestDiscarded(LeaveRequest $leaveRequest, $discarded){
        $creator = $leaveRequest->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => $leaveRequest->leaveType->name])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded', [
                    'request'  => strtolower(__('model.leave_request')), 
                    'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                    'leave_type' => strtolower($leaveRequest->leaveType->name),
                    'dates'  => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() .' - '.$leaveRequest->to_date->toDateString(),
                    'name'  => $discarded->approver_name
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);      
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $approvalRoles = collect($leaveRequest->approvalStatus->steps)->pluck('role_id')->toArray();
            $approvers = $leaveRequest->user->approvers->where('model_type', get_class($leaveRequest))->whereIn('role_id', $approvalRoles)->pluck('approver_id')->toArray();
            $receivers = User::whereIn('id', $approvers)->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.leave_request')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded', [
                        'request'  => strtolower(__('model.leave_request')), 
                        'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                        'leave_type' => strtolower($leaveRequest->leaveType->name),
                        'dates'  => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() .' - '.$leaveRequest->to_date->toDateString(),
                        'name'  => $discarded->approver_name
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                    ]
                ]);
                
                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $leaveRequest::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
        
    }

    protected function overtimeDiscarded(OverTime $overtime, $discarded){
        $creator = $overtime->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.overtime')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_overtime', [                            
                    'amount'    => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                    'date'      => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
                    'name'      => $discarded->approver_name,     
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $approvalRoles = collect($overtime->approvalStatus->steps)->pluck('role_id')->toArray();
            $approvers = $overtime->user->approvers->where('model_type', get_class($overtime))->whereIn('role_id', $approvalRoles)->pluck('approver_id')->toArray();
            $receivers = User::whereIn('id', $approvers)->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.overtime')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_overtime', [                            
                        'amount'    => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                        'date'      => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
                        'name'      => $discarded->approver_name,     
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                    ]
                ]);

                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $overtime::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
    }

    protected function switchWorkDayDiscarded(SwitchWorkDay $switchWorkDay, $discarded){
        $creator = $switchWorkDay->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.switch_work_day')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_switch_working_day', [                            
                    'from'    => $switchWorkDay->from_date->toDateString(),
                    'to'      => $switchWorkDay->to_date->toDateString(), 
                    'name'    => $discarded->approver_name,     
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
                ]
            ]);
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $approvalRoles = collect($switchWorkDay->approvalStatus->steps)->pluck('role_id')->toArray();
            $approvers = $switchWorkDay->user->approvers->where('model_type', get_class($switchWorkDay))->whereIn('role_id', $approvalRoles)->pluck('approver_id')->toArray();
            $receivers = User::whereIn('id', $approvers)->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.switch_work_day')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_switch_working_day', [                            
                        'from'    => $switchWorkDay->from_date->toDateString(),
                        'to'      => $switchWorkDay->to_date->toDateString(), 
                        'name'    => $discarded->approver_name,     
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
                    ]
                ]);

                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $switchWorkDay::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
    }

    protected function workFromHomeDiscarded(WorkFromHome $workFromHome, $discarded){
        $creator = $workFromHome->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.work_from_home')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_work_from_home', [    
                    'days'  => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),                         
                    'from'    => $workFromHome->from_date->toDateString(),
                    'to'      => $workFromHome->to_date->toDateString(), 
                    'name'    => $discarded->approver_name,     
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
                ]
            ]);
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $approvalRoles = collect($workFromHome->approvalStatus->steps)->pluck('role_id')->toArray();
            $approvers = $workFromHome->user->approvers->where('model_type', get_class($workFromHome))->whereIn('role_id', $approvalRoles)->pluck('approver_id')->toArray();
            $receivers = User::whereIn('id', $approvers)->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.work_from_home')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_work_from_home', [
                        'days'  => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),                             
                        'from'    => $workFromHome->from_date->toDateString(),
                        'to'      => $workFromHome->to_date->toDateString(), 
                        'name'    => $discarded->approver_name,     
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
                    ]
                ]);

                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $workFromHome::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
    }

    protected function purchaseRequestDiscarded(PurchaseRequest $purchaseRequest, $discarded){
        $creator = $purchaseRequest->approvalStatus->creator;
        if(Auth::id() != $creator->id){
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.purchase_request')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.purchase_request_discarded', [    
                    'number'        => strtoupper($purchaseRequest->pr_no), 
                    'actionedBy'    => $discarded->approver_name,     
                ]),
                'action'    => [
                    'name'  => __('btn.view'),
                    'url'   => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
                ]
            ]);
    
            // send notification
            $this->sendNotification($creator, $message, comment: $discarded->comment);
        }else{
            $approvalRoles = collect($purchaseRequest->approvalStatus->steps)->pluck('role_id')->toArray();
            $approvers = $purchaseRequest->user->approvers->where('model_type', get_class($purchaseRequest))->whereIn('role_id', $approvalRoles)->pluck('approver_id')->toArray();
            $receivers = User::whereIn('id', $approvers)->get();
            foreach($receivers as $receiver){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.purchase_request')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.purchase_request_discarded_by_owner', [
                        'name'      => $purchaseRequest->approvalStatus->creator->full_name,                             
                        'number'    => strtoupper($purchaseRequest->pr_no),
                    ]),
                    'action'    => [
                        'name'  => __('btn.view'),
                        'url'   => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
                    ]
                ]);

                // cc approver
                $ccEmails = [];
                $ccs = collect(app(SettingOptions::class)->cc_emails)->where('model_type', $purchaseRequest::getApprovableType())->first();
                if($ccs){
                    $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
                }
        
                // send notification
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc:$ccEmails);
            }
        }
    }
}

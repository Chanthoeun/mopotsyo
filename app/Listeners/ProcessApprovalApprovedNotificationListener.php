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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovedEvent;

class ProcessApprovalApprovedNotificationListener
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
    public function handle(ProcessApprovedEvent $event): void
    {        
        $approved = $event->approval;
        $approvable = $approved->approvable;            
        if($approvable->isApprovalCompleted() && get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestApprovedCompleted($approvable);
        }else if($approvable->isApprovalCompleted() && get_class($approvable) == OverTime::class){
            $this->overtimeApprovedCompleted($approvable, $approved);
        }else if($approvable->isApprovalCompleted() && get_class($approvable) == SwitchWorkDay::class){
            $this->switchWorkDayApprovedCompleted($approvable, $approved);
        }else if($approvable->isApprovalCompleted() && get_class($approvable) == WorkFromHome::class){
            $this->workFromHomeApprovedCompleted($approvable, $approved);
        }else if($approvable->isApprovalCompleted() && get_class($approvable) == PurchaseRequest::class){
            $this->purchaseRequestApprovedCompleted($approvable, $approved);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestApproved($approvable);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == OverTime::class) {
            $this->overtimeApproved($approvable, $approved);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == SwitchWorkDay::class) {
            $this->switchWorkDayApproved($approvable, $approved);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == WorkFromHome::class) {
            $this->workFromHomeApproved($approvable, $approved);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == PurchaseRequest::class) {
            $this->purchaseRequestApproved($approvable, $approved);
        }                     
    }

    protected function leaveRequestApprovedCompleted(LeaveRequest $leaveRequest){
        // reset unused overtime
        if(app(SettingOptions::class)->allow_overtime == true && app(SettingOptions::class)->overtime_link == $leaveRequest->leave_type_id && $leaveRequest->overTimes){
            $leaveRequest->overTimes()->update([
                'unused' => false
            ]);
        }

        $receiver = $leaveRequest->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => $leaveRequest->leaveType->name])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.completed_leave_request', [
                'request'  => strtolower(__('model.leave_request')), 
                'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                'leave_type' => strtolower($leaveRequest->leaveType->name),
                'dates'  => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() .' - '. $leaveRequest->to_date->toDateString()
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

        // cc department head
        if($receiver->department_head && $receiver->department_head->id != Auth::id()){
            $ccEmails = array_merge($ccEmails, [$receiver->department_head->email]);
        }

        $this->sendNotification($receiver, $message, cc:$ccEmails);
    }

    protected function leaveRequestApproved(LeaveRequest $leaveRequest){
        $nextStep = $leaveRequest->nextApprovalStep();
        $approval = $leaveRequest->user->approvers->where('model_type', get_class($leaveRequest))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => $leaveRequest->leaveType->name])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.approved_leave_request', [
                    'name'  => $leaveRequest->approvalStatus->creator->full_name, 
                    'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                    'leave_type' => strtolower($leaveRequest->leaveType->name),
                    'dates'  => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() .' - '. $leaveRequest->to_date->toDateString()
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message);
        }
    }
    
    protected function overtimeApprovedCompleted(OverTime $overtime){
        $receiver = $overtime->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => __('model.overtime')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.completed_overtime', [                
                'amount' => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                'date'  => $overtime->requestDates->implode('date', ', '), 
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

        $this->sendNotification($receiver, $message, cc:$ccEmails);
    }

    protected function overtimeApproved(OverTime $overtime){
        $nextStep = $overtime->nextApprovalStep();
        $approval = $overtime->user->approvers->where('model_type', get_class($overtime))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.overtime')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.overtime', [
                    'name'  => $overtime->approvalStatus->creator->full_name, 
                    'action'    => strtolower(__('msg.requested')),
                    'amount' => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                    'date'  => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message);
        }
    }
    
    protected function switchWorkDayApprovedCompleted(SwitchWorkDay $switchWorkDay){
        $receiver = $switchWorkDay->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => __('model.switch_work_day')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.completed_switch_working_day', [                
                'from' => $switchWorkDay->from_date->toDateString(),
                'to'  => $switchWorkDay->to_date->toDateString(), 
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

        $this->sendNotification($receiver, $message, cc:$ccEmails);
    }

    protected function switchWorkDayApproved(SwitchWorkDay $switchWorkDay){
        $nextStep = $switchWorkDay->nextApprovalStep();
        $approval = $switchWorkDay->user->approvers->where('model_type', get_class($switchWorkDay))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.switch_work_day')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.switch_working_day', [
                    'name'  => $switchWorkDay->approvalStatus->creator->full_name, 
                    'from'  => $switchWorkDay->from_date->toDateString(),
                    'to'    => $switchWorkDay->to_date->toDateString(), 
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $switchWorkDay->reason);
        }
    }

    protected function workFromHomeApprovedCompleted(WorkFromHome $workFromHome){
        $receiver = $workFromHome->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => __('model.work_from_home')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.completed_work_from_home', [   
                'days'  => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),           
                'from' => $workFromHome->from_date->toDateString(),
                'to'  => $workFromHome->to_date->toDateString(), 
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

        $this->sendNotification($receiver, $message, cc:$ccEmails);
    }

    protected function workFromHomeApproved(WorkFromHome $workFromHome){
        $nextStep = $workFromHome->nextApprovalStep();
        $approval = $workFromHome->user->approvers->where('model_type', get_class($workFromHome))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.work_from_home')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.work_from_home', [
                    'name'  => $workFromHome->approvalStatus->creator->full_name, 
                    'days'  => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),  
                    'from'  => $workFromHome->from_date->toDateString(),
                    'to'    => $workFromHome->to_date->toDateString(), 
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $workFromHome->reason);
        }
    }

    protected function purchaseRequestApprovedCompleted(PurchaseRequest $purchaseRequest){
        $receiver = $purchaseRequest->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => __('model.purchase_request')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.purchase_request_completed', [   
                'number'        => strtoupper($purchaseRequest->pr_no),                
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

        $this->sendNotification($receiver, $message, cc:$ccEmails);
    }

    protected function purchaseRequestApproved(PurchaseRequest $purchaseRequest, $approved){   
        $nextStep = $purchaseRequest->nextApprovalStep();
        $approval = $purchaseRequest->user->approvers->where('model_type', get_class($purchaseRequest))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.purchase_request')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.purchase_request_approved', [
                    'name'      => $purchaseRequest->approvalStatus->creator->full_name, 
                    'action'    => strtolower(__('btn.request')),  
                    'number'    => strtoupper($purchaseRequest->pr_no),
                    'actionedBy'=> $approved->approver_name, 
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $purchaseRequest->purpose);
        }
    }
}

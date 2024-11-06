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
use App\Traits\SendNotification; 
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;

class ProcessApprovalSubmittedNotificationListener
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
    public function handle(ProcessSubmittedEvent $event): void
    {
        $approvable = $event->approvable;   
        if(get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestSubmitted($approvable);
        }else if(get_class($approvable) == OverTime::class){
            $this->overTimeSubmitted($approvable);
        }else if(get_class($approvable) == SwitchWorkDay::class){
            $this->switchWorkDaySubmitted($approvable);
        }else if(get_class($approvable) == WorkFromHome::class){
            $this->workFromHomeSubmitted($approvable);
        }else if(get_class($approvable) == PurchaseRequest::class){
            $this->purchaseRequestSubmitted($approvable);
        }
    }

    protected function leaveRequestSubmitted(LeaveRequest $leaveRequest)
    {
        $nextStep = $leaveRequest->nextApprovalStep();
        $approval = $leaveRequest->user->approvers->where('model_type', get_class($leaveRequest))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => $leaveRequest->leaveType->name])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.submit_leave_request', [
                    'name'  => $leaveRequest->approvalStatus->creator->full_name, 
                    'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                    'leave_type' => strtolower($leaveRequest->leaveType->name),
                    'dates'  => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() .' - '. $leaveRequest->to_date->toDateString(),
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);
    
            // send notification
            $this->sendNotification($approver, $message, comment: $leaveRequest->reason);
        }
    }

    protected function overtimeSubmitted(OverTime $overtime)
    {
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
                    'date'  => $overtime->requestDates->implode('date', ', '), 
                ]),
                'action'    => [
                    'name'  => __('btn.decide'),
                    'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $overtime->reason);
        }
    }

    protected function switchWorkDaySubmitted(SwitchWorkDay $switchWorkDay)
    {
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
                    'to'    => $switchWorkDay->to_date->toDateString()
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

    protected function workFromHomeSubmitted(WorkFromHome $workFromHome)
    {
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
                    'to'    => $workFromHome->to_date->toDateString()
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

    protected function purchaseRequestSubmitted(PurchaseRequest $purchaseRequest)
    {
        $nextStep = $purchaseRequest->nextApprovalStep();
        $approval = $purchaseRequest->user->approvers->where('model_type', get_class($purchaseRequest))->where('role_id', $nextStep->role_id)->first();
        if($approval){
            $approver = $approval->approver;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.purchase_request')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.purchase_request', [
                    'name'      => $purchaseRequest->approvalStatus->creator->full_name, 
                    'action'    => strtolower(__('btn.request')),
                    'number'    => strtoupper($purchaseRequest->pr_no)
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

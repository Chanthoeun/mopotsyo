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
use App\Models\WorkFromHome;
use App\Traits\SendNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RingleSoft\LaravelProcessApproval\Events\ProcessRejectedEvent;

class ProcessApprovalRejectedNotificationListener
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
    public function handle(ProcessRejectedEvent $event): void
    {
        $rejected = $event->approval;
        $approvable = $rejected->approvable;
        if(get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestRejected($approvable, $rejected);
        }else if(get_class($approvable) == OverTime::class){
            $this->overtimeRejected($approvable, $rejected);
        }else if(get_class($approvable) == SwitchWorkDay::class){
            $this->switchWorkDayRejected($approvable, $rejected);
        }else if(get_class($approvable) == WorkFromHome::class){
            $this->workFromHomeRejected($approvable, $rejected);
        }else if(get_class($approvable) == PurchaseRequest::class){
            $this->purchaseRequestRejected($approvable, $rejected);
        }
    }

    protected function leaveRequestRejected(LeaveRequest $leaveRequest, $rejected){
        $receiver = $leaveRequest->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.rejected', ['label' => $leaveRequest->leaveType->name])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.rejected', [
                'request'  => strtolower(__('model.leave_request')), 
                'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                'leave_type' => strtolower($leaveRequest->leaveType->name),
                'from'  => $leaveRequest->from_date->toDateString(), 
                'to' => $leaveRequest->to_date->toDateString(),
                'name'  => $rejected->approver_name
            ]),
            'action'    => [
                'name'  => __('btn.view'),
                'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
            ]
        ]);

        // send notification
        $this->sendNotification($receiver, $message, comment: $rejected->comment);
    }

    protected function overtimeRejected(OverTime $overtime, $rejected){
        $receiver = $overtime->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.rejected', ['label' => __('model.overtime')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.rejected_overtime', [                            
                'amount'    => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                'date'      => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
                'name'      => $rejected->approver_name,     
            ]),
            'action'    => [
                'name'  => __('btn.view'),
                'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
            ]
        ]);

        // send notification
        $this->sendNotification($receiver, $message, comment: $rejected->comment);
    }

    protected function switchWorkDayRejected(SwitchWorkDay $switchWorkDay, $rejected){
        $receiver = $switchWorkDay->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.rejected', ['label' => __('model.switch_work_day')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.rejected_switch_working_day', [                            
                'from'    => $switchWorkDay->from_date->toDateString(),
                'to'      => $switchWorkDay->to_date->toDateString(), 
                'name'    => $rejected->approver_name,     
            ]),
            'action'    => [
                'name'  => __('btn.view'),
                'url'   => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
            ]
        ]);

        // send notification
        $this->sendNotification($receiver, $message, comment: $rejected->comment);
    }

    protected function workFromHomeRejected(WorkFromHome $workFromHome, $rejected){
        $receiver = $workFromHome->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.rejected', ['label' => __('model.work_from_home')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.rejected_work_from_home', [    
                'days'    => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),                         
                'from'    => $workFromHome->from_date->toDateString(),
                'to'      => $workFromHome->to_date->toDateString(), 
                'name'    => $rejected->approver_name,     
            ]),
            'action'    => [
                'name'  => __('btn.view'),
                'url'   => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
            ]
        ]);

        // send notification
        $this->sendNotification($receiver, $message, comment: $rejected->comment);
    }

    protected function purchaseRequestRejected(PurchaseRequest $purchaseRequest, $rejected){
        $receiver = $purchaseRequest->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.rejected', ['label' => __('model.work_from_home')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.purchase_request_rejected', [    
                'number'  => strtoupper($purchaseRequest->pr_no), 
                'actionedBy'    => $rejected->approver_name,     
            ]),
            'action'    => [
                'name'  => __('btn.view'),
                'url'   => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
            ]
        ]);

        // send notification
        $this->sendNotification($receiver, $message, comment: $rejected->comment);
    }
}

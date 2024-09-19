<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\LeaveRequest;
use App\Models\OverTime;
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
}

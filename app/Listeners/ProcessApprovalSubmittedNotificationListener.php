<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\LeaveRequest;
use App\Models\OverTime;
use App\Models\User;
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
        }
    }

    protected function leaveRequestSubmitted(LeaveRequest $leaveRequest)
    {
        $approvers = collect();
        $nextApproval = $leaveRequest->nextApprovalStep();
        $getApprover = $leaveRequest->processApprovers()->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();

        if($getApprover){
            if($getApprover->approver){
                $approvers->push($getApprover->approver);
            }else{
                $approvers = User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->role($getApprover->role_id)->get();
            }
        }else{
            $approvers = User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->role($nextApproval->role_id)->get();
        }
        
        
        
        foreach($approvers as $approver){            
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => $leaveRequest->leaveType->name])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.submit_leave_request', [
                    'name'  => $leaveRequest->approvalStatus->creator->full_name, 
                    'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                    'leave_type' => strtolower($leaveRequest->leaveType->name),
                    'from'  => $leaveRequest->from_date->toDateString(), 
                    'to' => $leaveRequest->to_date->toDateString()
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);
    
            // send notification
            $this->sendNotification($approver, $message);
        }
    }

    protected function overtimeSubmitted(OverTime $overtime)
    {
        $approvers = collect();
        $nextApproval = $overtime->nextApprovalStep();
        $processApprover = $overtime->processApprovers()->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();
        
        if($processApprover){
            if($processApprover->approver){
                $approvers->push($processApprover->approver);
            }else{
                $approvers = User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->role($processApprover->role_id)->get();
            }
        }else{
            $approvers = User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->role($nextApproval->role_id)->get();
        }
        
        foreach($approvers as $approver){            
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
                    'name'  => __('btn.approve'),
                    'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message);
        }
    }
}

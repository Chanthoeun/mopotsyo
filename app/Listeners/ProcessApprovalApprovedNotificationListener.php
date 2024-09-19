<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Models\LeaveRequest;
use App\Models\OverTime;
use App\Models\User;
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
        $approvable = $event->approval->approvable;            
        if($approvable->isApprovalCompleted() && get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestApprovedCompleted($approvable);
        }else if($approvable->isApprovalCompleted() && get_class($approvable) == OverTime::class){
            $this->overtimeApprovedCompleted($approvable);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == LeaveRequest::class){
            $this->leaveRequestApproved($approvable);
        }else if(!$approvable->isApprovalCompleted() && get_class($approvable) == OverTime::class) {
            $this->overtimeApproved($approvable);
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
            'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => $leaveRequest->leaveType->name])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.completed_leave_request', [
                'request'  => strtolower(__('model.leave_request')), 
                'days'  => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                'leave_type' => strtolower($leaveRequest->leaveType->name),
                'from'  => $leaveRequest->from_date->toDateString(), 
                'to' => $leaveRequest->to_date->toDateString()
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
        $approvers = collect();
        $nextApproval = $leaveRequest->nextApprovalStep();
        $getApprover = $leaveRequest->processApprovers->where('step_id', $nextApproval->process_approval_flow_step_id)->where('role_id', $nextApproval->role_id)->first();
        // send notification to approver
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
            if(Auth::id() != $approver->id){
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => $leaveRequest->leaveType->name])]),
                    'greeting' => __('mail.greeting', ['name' => $approver->name]),
                    'body' => __('msg.body.approved_leave_request', [
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
    }
    
    protected function overtimeApprovedCompleted(OverTime $overtime){
        $receiver = $overtime->approvalStatus->creator;
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => __('model.overtime')])]),
            'greeting' => __('mail.greeting', ['name' => $receiver->name]),
            'body' => __('msg.body.completed_overtime', [                
                'amount' => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                'date'  => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()), 
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
        $approvers = collect();
        $nextApproval = $overtime->nextApprovalStep();
        $getApprover = $overtime->processApprovers()->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();
        
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
            if(Auth::id() != $approver->id){
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
}

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
                    'dates'  => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() .' - '.$leaveRequest->to_date->toDateString(),
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);
    
            // send notification
            $this->sendNotification($approver, $message, comment: $leaveRequest->reason);
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
                    'date'  => $overtime->requestDates->implode('date', ', '), 
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $overtime->reason);
        }
    }

    protected function switchWorkDaySubmitted(SwitchWorkDay $switchWorkDay)
    {
        $approvers = collect();
        $nextApproval = $switchWorkDay->nextApprovalStep();
        $processApprover = $switchWorkDay->processApprovers()->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();
        
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
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.switch_work_day')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.switch_working_day', [
                    'name'  => $switchWorkDay->approvalStatus->creator->full_name, 
                    'from'  => $switchWorkDay->from_date->toDateString(),
                    'to'    => $switchWorkDay->to_date->toDateString()
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $switchWorkDay->reason);
        }
    }

    protected function workFromHomeSubmitted(WorkFromHome $workFromHome)
    {
        $approvers = collect();
        $nextApproval = $workFromHome->nextApprovalStep();
        $processApprover = $workFromHome->processApprovers()->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();
        
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
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.switch_work_day')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.work_from_home', [
                    'name'  => $workFromHome->approvalStatus->creator->full_name, 
                    'days'  => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),
                    'from'  => $workFromHome->from_date->toDateString(),
                    'to'    => $workFromHome->to_date->toDateString()
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $workFromHome->reason);
        }
    }

    protected function purchaseRequestSubmitted(PurchaseRequest $purchaseRequest)
    {
        $approvers = collect();
        $nextApproval = $purchaseRequest->nextApprovalStep();
        $processApprover = $purchaseRequest->processApprovers()->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();
        
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
                'subject' => __('mail.subject', ['name' => __('btn.label.request', ['label' => __('model.purchase_request')])]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.purchase_request', [
                    'name'      => $purchaseRequest->approvalStatus->creator->full_name, 
                    'action'    => strtolower(__('btn.request')),
                    'number'    => strtoupper($purchaseRequest->pr_no)
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
                ]
            ]);

            // send notification
            $this->sendNotification($approver, $message, comment: $purchaseRequest->purpose);
        }
    }
}

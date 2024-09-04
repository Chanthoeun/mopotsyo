<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\ProcessApprover;
use App\Models\User;
use App\Notifications\SendLeaveRequestNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;

class LeaveRequestSubmittedNotificationListener
{
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
        $nextApproval = $approvable->nextApprovalStep();
        $getApprover = ProcessApprover::where('leave_request_id', $approvable->id)->where('step_id', $nextApproval->id)->where('role_id', $nextApproval->role_id)->first();
        

        // send notification to approver
        $approvers = collect();
        if($getApprover && $getApprover->user){
            $approvers->push($getApprover->user);
        }else{
            $approvers = User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->role($getApprover->role_id)->get();
        }
        
        foreach($approvers as $approver){            
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('model.leave_request')]),
                'greeting' => __('mail.greeting', ['name' => $approver->name]),
                'body' => __('msg.body.submit_leave_request', [
                    'name'  => $approvable->approvalStatus->creator->full_name, 
                    'days'  => strtolower(trans_choice('field.days_with_count', $approvable->days, ['count' => $approvable->days])),
                    'leave_type' => strtolower($approvable->leaveType->name),
                    'from'  => $approvable->from_date->toDateString(), 
                    'to' => $approvable->to_date->toDateString()
                ]),
                'action'    => [
                    'name'  => __('btn.approve'),
                    'url'   => LeaveRequestResource::getUrl('view', ['record' => $approvable])
                ]
            ]);
    
            // send noti
            Notification::make()
                ->success()
                ->icon('fas-user-clock')
                ->iconColor('success')
                ->title($message['subject'])
                ->body($message['body'])
                ->actions([               
                    Action::make('view')
                        ->label($message['action']['name'])
                        ->button()
                        ->url($message['action']['url']) 
                        ->icon('fas-eye')                   
                        ->markAsRead(),
                ])
                ->sendToDatabase($approver);
            
            $approver->notify(new SendLeaveRequestNotification($message));
        }
    }
}

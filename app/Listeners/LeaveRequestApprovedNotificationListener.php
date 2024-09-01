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
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovedEvent;

class LeaveRequestApprovedNotificationListener
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
    public function handle(ProcessApprovedEvent $event): void
    {
        $nextApproval = $event->approval;    
        $approvable = $nextApproval->approvable; 
        if(!$approvable->isApprovalCompleted()){
            $getApprover = ProcessApprover::where('leave_request_id', $approvable->id)->where('step_id', $nextApproval->process_approval_flow_step_id)->first();
            // send notification to approver
            if($getApprover->user){
                $approvers = collect()->push($getApprover->user);
            }else{
                $approvers = User::whereHas('employee', fn(Builder $q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', now()))->role($getApprover->role_id)->get();
            }
            
            foreach($approvers as $approver){            
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('model.leave_request')]),
                    'greeting' => __('mail.greeting', ['name' => $approver->name]),
                    'body' => __('msg.body.approved_leave_request', [
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
        }else{
            $leaveRequest = $approvable;
            $receiver = $approvable->approvalStatus->creator;
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.completed', ['label' => __('model.leave_request')])]),
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
                ->sendToDatabase($receiver);
            
            $receiver->notify(new SendLeaveRequestNotification($message));
        }        
    }
}

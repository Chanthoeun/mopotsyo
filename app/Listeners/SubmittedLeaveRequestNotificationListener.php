<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Notifications\SendLeaveRequestNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent;

class SubmittedLeaveRequestNotificationListener
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
        $approvable->approvalStatus->creator->supervisor->assignRole($approvable->approvalStatus->steps[0]['role_name']);

        // send notification to approver
        $approver = $approvable->approvalStatus->creator->supervisor;
        
        $message = collect([
            'subject' => __('mail.subject', ['name' => __('model.leave_request')]),
            'greeting' => __('mail.greeting', ['name' => $approver->name]),
            'body' => __('msg.body.submit_leave_request', [
                'name'  => $approvable->approvalStatus->creator->full_name, 
                'days'  => trans_choice('field.days_with_count', $approvable->days, ['count' => $approvable->days]),
                'leave_type' => $approvable->leaveType->name,
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

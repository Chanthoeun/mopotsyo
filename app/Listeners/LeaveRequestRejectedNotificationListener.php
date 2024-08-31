<?php

namespace App\Listeners;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Notifications\SendLeaveRequestNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use RingleSoft\LaravelProcessApproval\Events\ProcessRejectedEvent;

class LeaveRequestRejectedNotificationListener
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
    public function handle(ProcessRejectedEvent $event): void
    {
        $rejected = $event->approval;
        $leaveRequest = $rejected->approvable;
        $receiver = $leaveRequest->approvalStatus->creator;

        $message = collect([
            'subject' => __('mail.subject', ['name' => __('msg.label.rejected', ['label' => __('model.leave_request')])]),
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
        
        $receiver->notify(new SendLeaveRequestNotification($message, $rejected->comment));

    }
}

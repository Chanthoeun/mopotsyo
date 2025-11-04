<?php

namespace App\Listeners;

/**
 * This listener handles notifications for various types of approval processes
 * including leave requests, overtime, switch work days, work from home, and purchase requests.
 */

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
     * Handle the process submitted event.
     * Routes the approval request to the appropriate handler based on the request type.
     *
     * @param ProcessSubmittedEvent $event The event containing the approvable model
     */
    public function handle(ProcessSubmittedEvent $event): void
    {
        $approvable = $event->approvable;
        // Use a match expression for cleaner dispatching based on approvable type.
        match (get_class($approvable)) {
            LeaveRequest::class => $this->leaveRequestSubmitted($approvable),
            OverTime::class => $this->overtimeSubmitted($approvable),
            SwitchWorkDay::class => $this->switchWorkDaySubmitted($approvable),
            WorkFromHome::class => $this->workFromHomeSubmitted($approvable),
            PurchaseRequest::class => $this->purchaseRequestSubmitted($approvable),
            default => null, // Handle unknown approvable types gracefully.
        };
    }

    /**
     * Handle notification for submitted leave requests.
     * Sends a notification to the next approver in the workflow.
     *
     * @param LeaveRequest $leaveRequest The submitted leave request
     */
    protected function leaveRequestSubmitted(LeaveRequest $leaveRequest)
    {
        // Get the next approver in the workflow.
        $approver = $this->getNextApprover($leaveRequest);
        if ($approver) {
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

    /**
     * Handle notification for submitted overtime requests.
     * Sends a notification to the next approver in the workflow.
     *
     * @param OverTime $overtime The submitted overtime request
     */
    protected function overtimeSubmitted(OverTime $overtime): void
    {
        // Get the next approver in the workflow.
        $approver = $this->getNextApprover($overtime);
        if ($approver) {
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

    /**
     * Handle notification for submitted switch work day requests.
     * Sends a notification to the next approver in the workflow.
     *
     * @param SwitchWorkDay $switchWorkDay The submitted switch work day request
     */
    protected function switchWorkDaySubmitted(SwitchWorkDay $switchWorkDay): void
    {
        // Get the next approver in the workflow.
        $approver = $this->getNextApprover($switchWorkDay);
        if ($approver) {
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

    /**
     * Handle notification for submitted work from home requests.
     * Sends a notification to the next approver in the workflow.
     *
     * @param WorkFromHome $workFromHome The submitted work from home request
     */
    protected function workFromHomeSubmitted(WorkFromHome $workFromHome): void
    {
        // Get the next approver in the workflow.
        $approver = $this->getNextApprover($workFromHome);
        if ($approver) {
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

    /**
     * Handle notification for submitted purchase requests.
     * Sends a notification to the next approver in the workflow.
     *
     * @param PurchaseRequest $purchaseRequest The submitted purchase request
     */
    protected function purchaseRequestSubmitted(PurchaseRequest $purchaseRequest): void
    {
        // Get the next approver in the workflow.
        $approver = $this->getNextApprover($purchaseRequest);
        if ($approver) {
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

    /**
     * Get the next approver for a given approvable model.
     *
     * @param mixed $approvable The model instance that is approvable.
     * @return User|null The next approver user model, or null if not found.
     */
    private function getNextApprover(mixed $approvable): ?User
    {
        $nextStep = $approvable->nextApprovalStep();
        if (!$nextStep) {
            return null;
        }

        $approval = $approvable->user->approvers->where('model_type', get_class($approvable))->where('role_id', $nextStep->role_id)->first();

        return $approval?->approver;
    }
}

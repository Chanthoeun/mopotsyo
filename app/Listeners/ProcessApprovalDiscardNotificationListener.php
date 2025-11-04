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
use App\Settings\SettingOptions;
use App\Traits\SendNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval; // Corrected type hint for approval model

/**
 * Listener for when an approval process is discarded.
 * This listener sends notifications to the creator or relevant approvers based on who discarded the request.
 */
class ProcessApprovalDiscardNotificationListener
{
    use SendNotification;
    /**
     * Create the event listener. No specific initialization needed.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * Dispatches the notification logic based on the type of the approvable model.
     *
     * @param ProcessDiscardedEvent $event The event containing the discarded approval and approvable model.
     */
    public function handle(ProcessDiscardedEvent $event): void
    {
        $discarded = $event->approval;
        $approvable = $discarded->approvable;

        // Use a match expression for cleaner dispatching based on approvable type (PHP 8+)
        match (get_class($approvable)) {
            LeaveRequest::class => $this->leaveRequestDiscarded($approvable, $discarded),
            OverTime::class => $this->overtimeDiscarded($approvable, $discarded),
            SwitchWorkDay::class => $this->switchWorkDayDiscarded($approvable, $discarded),
            WorkFromHome::class => $this->workFromHomeDiscarded($approvable, $discarded),
            PurchaseRequest::class => $this->purchaseRequestDiscarded($approvable, $discarded),
            default => null, // Handle unknown approvable types gracefully, or throw an exception
        };
    }

    /**
     * Retrieves CC email addresses for a given approvable model based on settings.
     *
     * @param object $approvable The approvable model instance.
     * @return array An array of email addresses to CC.
     */
    private function getCcEmailsForApprovable(object $approvable): array
    {
        $ccEmails = [];
        // Retrieve CC email settings for the specific approvable type
        $ccs = collect(app(SettingOptions::class)->cc_emails)
                ->where('model_type', $approvable::getApprovableType())
                ->first();

        // If CC settings exist and accounts are specified, fetch their emails
        if ($ccs && !empty($ccs['accounts'])) {
            $ccEmails = User::whereIn('id', $ccs['accounts'])->get()->pluck('email')->toArray();
        }
        return $ccEmails;
    }

    /**
     * Retrieves User models for all approvers associated with the given approvable model's current approval steps.
     *
     * @param object $approvable The approvable model instance.
     * @return \Illuminate\Database\Eloquent\Collection A collection of User models who are approvers.
     */
    private function getApproversForApprovable(object $approvable): \Illuminate\Database\Eloquent\Collection
    {
        // Get the role IDs for the current approval steps
        $approvalRoles = collect($approvable->approvalStatus->steps)->pluck('role_id')->toArray();
        // Find the approver IDs associated with these roles for the specific model type
        $approverIds = $approvable->user->approvers
                                    ->where('model_type', get_class($approvable))
                                    ->whereIn('role_id', $approvalRoles)
                                    ->pluck('approver_id')
                                    ->toArray();
        // Fetch the User models for these approver IDs
        return User::whereIn('id', $approverIds)->get();
    }

    /**
     * Handles the notification for a discarded LeaveRequest.
     *
     * @param LeaveRequest $leaveRequest The discarded leave request model.
     * @param Approval $discarded The approval record indicating the discard action.
     */
    protected function leaveRequestDiscarded(LeaveRequest $leaveRequest, ProcessApproval $discarded): void
    {
        // Get the creator of the leave request
        $creator = $leaveRequest->approvalStatus->creator;
        // Get CC emails relevant to LeaveRequest
        $ccEmails = $this->getCcEmailsForApprovable($leaveRequest);

        // Determine if the discarder is the creator or an approver
        if ($discarded->approver_id != $creator->id) {
            // Case 1: An approver discarded the request. Notify the creator.
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => $leaveRequest->leaveType->name])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded', [
                    'request' => strtolower(__('model.leave_request')),
                    'days' => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                    'leave_type' => strtolower($leaveRequest->leaveType->name),
                    'dates' => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() . ' - ' . $leaveRequest->to_date->toDateString(),
                    'name' => $discarded->approver_name // Name of the person who discarded it
                ]),
                'action' => [
                    'name' => __('btn.view'),
                    'url' => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                ]
            ]);

            // Send notification to the creator
            $this->sendNotification($creator, $message, comment: $discarded->comment, cc: $ccEmails);
        } else {
            // Case 2: The creator discarded their own request. Notify all relevant approvers.
            $receivers = $this->getApproversForApprovable($leaveRequest);

            foreach ($receivers as $receiver) {
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.leave_request')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded', [
                        'request' => strtolower(__('model.leave_request')),
                        'days' => strtolower(trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days])),
                        'leave_type' => strtolower($leaveRequest->leaveType->name),
                        'dates' => $leaveRequest->days <= 2 ? $leaveRequest->requestDates->implode('date', ', ') : $leaveRequest->from_date->toDateString() . ' - ' . $leaveRequest->to_date->toDateString(),
                        'name' => $discarded->approver_name // This will be the creator's name
                    ]),
                    'action' => [
                        'name' => __('btn.view'),
                        'url' => LeaveRequestResource::getUrl('view', ['record' => $leaveRequest])
                    ]
                ]);

                // Send notification to each approver
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc: $ccEmails);
            }
        }
    }

    /**
     * Handles the notification for a discarded OverTime request.
     *
     * @param OverTime $overtime The discarded overtime request model.
     * @param Approval $discarded The approval record indicating the discard action.
     */
    protected function overtimeDiscarded(OverTime $overtime, ProcessApproval $discarded): void
    {
        // Get the creator of the overtime request
        $creator = $overtime->approvalStatus->creator;
        // Get CC emails relevant to OverTime
        $ccEmails = $this->getCcEmailsForApprovable($overtime);

        // Determine if the discarder is the creator or an approver
        if ($discarded->approver_id != $creator->id) {
            // Case 1: An approver discarded the request. Notify the creator.
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.overtime')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_overtime', [
                    'amount' => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                    'date' => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()),
                    'name' => $discarded->approver_name,
                ]),
                'action' => [
                    'name' => __('btn.view'),
                    'url' => OverTimeResource::getUrl('view', ['record' => $overtime])
                ]
            ]);

            // Send notification to the creator
            $this->sendNotification($creator, $message, comment: $discarded->comment, cc: $ccEmails);
        } else {
            // Case 2: The creator discarded their own request. Notify all relevant approvers.
            $receivers = $this->getApproversForApprovable($overtime);

            foreach ($receivers as $receiver) {
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.overtime')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_overtime', [
                        'amount' => strtolower(trans_choice('field.hours_with_count', $overtime->hours, ['count' => $overtime->hours])),
                        'date' => implode(', ', $overtime->requestDates->map(fn($requestDate) => ['date' => $requestDate->date->toDateString()])->pluck('date')->toArray()),
                        'name' => $discarded->approver_name, // This will be the creator's name
                    ]),
                    'action' => [
                        'name' => __('btn.view'),
                        'url' => OverTimeResource::getUrl('view', ['record' => $overtime])
                    ]
                ]);

                // Send notification to each approver
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc: $ccEmails);
            }
        }
    }

    /**
     * Handles the notification for a discarded SwitchWorkDay request.
     *
     * @param SwitchWorkDay $switchWorkDay The discarded switch work day request model.
     * @param Approval $discarded The approval record indicating the discard action.
     */
    protected function switchWorkDayDiscarded(SwitchWorkDay $switchWorkDay, ProcessApproval $discarded): void
    {
        // Get the creator of the switch work day request
        $creator = $switchWorkDay->approvalStatus->creator;
        // Get CC emails relevant to SwitchWorkDay
        $ccEmails = $this->getCcEmailsForApprovable($switchWorkDay);

        // Determine if the discarder is the creator or an approver
        if ($discarded->approver_id != $creator->id) {
            // Case 1: An approver discarded the request. Notify the creator.
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.switch_work_day')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_switch_working_day', [
                    'from' => $switchWorkDay->from_date->toDateString(),
                    'to' => $switchWorkDay->to_date->toDateString(),
                    'name' => $discarded->approver_name,
                ]),
                'action' => [
                    'name' => __('btn.view'),
                    'url' => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
                ]
            ]);

            // Send notification to the creator
            $this->sendNotification($creator, $message, comment: $discarded->comment, cc: $ccEmails);
        } else {
            // Case 2: The creator discarded their own request. Notify all relevant approvers.
            $receivers = $this->getApproversForApprovable($switchWorkDay);

            foreach ($receivers as $receiver) {
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.switch_work_day')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_switch_working_day', [
                        'from' => $switchWorkDay->from_date->toDateString(),
                        'to' => $switchWorkDay->to_date->toDateString(),
                        'name' => $discarded->approver_name, // This will be the creator's name
                    ]),
                    'action' => [
                        'name' => __('btn.view'),
                        'url' => SwitchWorkDayResource::getUrl('view', ['record' => $switchWorkDay])
                    ]
                ]);

                // Send notification to each approver
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc: $ccEmails);
            }
        }
    }

    /**
     * Handles the notification for a discarded WorkFromHome request.
     *
     * @param WorkFromHome $workFromHome The discarded work from home request model.
     * @param Approval $discarded The approval record indicating the discard action.
     */
    protected function workFromHomeDiscarded(WorkFromHome $workFromHome, ProcessApproval $discarded): void
    {
        // Get the creator of the work from home request
        $creator = $workFromHome->approvalStatus->creator;
        // Get CC emails relevant to WorkFromHome
        $ccEmails = $this->getCcEmailsForApprovable($workFromHome);

        // Determine if the discarder is the creator or an approver
        if ($discarded->approver_id != $creator->id) {
            // Case 1: An approver discarded the request. Notify the creator.
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.work_from_home')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.discarded_work_from_home', [
                    'days' => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),
                    'from' => $workFromHome->from_date->toDateString(),
                    'to' => $workFromHome->to_date->toDateString(),
                    'name' => $discarded->approver_name,
                ]),
                'action' => [
                    'name' => __('btn.view'),
                    'url' => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
                ]
            ]);

            // Send notification to the creator
            $this->sendNotification($creator, $message, comment: $discarded->comment, cc: $ccEmails);
        } else {
            // Case 2: The creator discarded their own request. Notify all relevant approvers.
            $receivers = $this->getApproversForApprovable($workFromHome);

            foreach ($receivers as $receiver) {
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.work_from_home')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.discarded_work_from_home', [
                        'days' => strtolower(trans_choice('field.days_with_count', $workFromHome->days, ['count' => $workFromHome->days])),
                        'from' => $workFromHome->from_date->toDateString(),
                        'to' => $workFromHome->to_date->toDateString(),
                        'name' => $discarded->approver_name, // This will be the creator's name
                    ]),
                    'action' => [
                        'name' => __('btn.view'),
                        'url' => WorkFromHomeResource::getUrl('view', ['record' => $workFromHome])
                    ]
                ]);

                // Send notification to each approver
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc: $ccEmails);
            }
        }
    }

    /**
     * Handles the notification for a discarded PurchaseRequest.
     *
     * @param PurchaseRequest $purchaseRequest The discarded purchase request model.
     * @param Approval $discarded The approval record indicating the discard action.
     */
    protected function purchaseRequestDiscarded(PurchaseRequest $purchaseRequest, ProcessApproval $discarded): void
    {
        // Get the creator of the purchase request
        $creator = $purchaseRequest->approvalStatus->creator;
        // Get CC emails relevant to PurchaseRequest
        $ccEmails = $this->getCcEmailsForApprovable($purchaseRequest);

        // Determine if the discarder is the creator or an approver
        if ($discarded->approver_id != $creator->id) {
            // Case 1: An approver discarded the request. Notify the creator.
            $message = collect([
                'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.purchase_request')])]),
                'greeting' => __('mail.greeting', ['name' => $creator->name]),
                'body' => __('msg.body.purchase_request_discarded', [
                    'number' => strtoupper($purchaseRequest->pr_no),
                    'actionedBy' => $discarded->approver_name,
                ]),
                'action' => [
                    'name' => __('btn.view'),
                    'url' => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
                ]
            ]);

            // Send notification to the creator
            $this->sendNotification($creator, $message, comment: $discarded->comment, cc: $ccEmails);
        } else {
            // Case 2: The creator discarded their own request. Notify all relevant approvers.
            $receivers = $this->getApproversForApprovable($purchaseRequest);

            foreach ($receivers as $receiver) {
                $message = collect([
                    'subject' => __('mail.subject', ['name' => __('msg.label.discarded', ['label' => __('model.purchase_request')])]),
                    'greeting' => __('mail.greeting', ['name' => $receiver->name]),
                    'body' => __('msg.body.purchase_request_discarded_by_owner', [
                        'name' => $purchaseRequest->approvalStatus->creator->full_name,
                        'number' => strtoupper($purchaseRequest->pr_no),
                    ]),
                    'action' => [
                        'name' => __('btn.view'),
                        'url' => PurchaseRequestResource::getUrl('view', ['record' => $purchaseRequest])
                    ]
                ]);

                // Send notification to each approver
                $this->sendNotification($receiver, $message, comment: $discarded->comment, cc: $ccEmails);
            }
        }
    }
}

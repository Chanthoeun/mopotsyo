<?php

namespace App\Listeners;

use App\Events\ApprovalProcessed;
use App\Notifications\SendEmailNotification;
use App\Settings\SettingOptions;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApprovalNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ApprovalProcessed $event): void
    {
        $record = $event->record;
        $action = $event->action;
        $comment = $event->comment;

        switch ($action) {
            case 'submitted':
                $this->notifyNextApprover($record);
                break;
            case \App\Enums\Status::APPROVED->value:
                if ($record->isApproved()) {
                    $this->notifyRecipient($record->user, $record, \App\Enums\Status::APPROVED->value, $comment, $event->actor);
                } else {
                    $this->notifyNextApprover($record);
                    $this->notifyRecipient($record->user, $record, 'partially_approved', $comment, $event->actor);
                }
                break;
            case \App\Enums\Status::REJECTED->value:
                $this->notifyRecipient($record->user, $record, \App\Enums\Status::REJECTED->value, $comment, $event->actor);
                break;
            case \App\Enums\Status::DISCARDED->value:
                $this->notifyDiscard($record, $comment, $event->actor);
                break;
        }
    }

    protected function notifyNextApprover($record): void
    {
        $step = $record->currentApprovalStep();
        if ($step && $step->approver) {
            $ownerName = $record->user?->full_name ?? 'Unknown';
            $modelName = $this->getModelName($record);
            $title = __('msg.label.pending_approval_request', ['model' => $modelName, 'name' => $ownerName]);
            $body = __('msg.body.pending_approval', ['model' => $modelName, 'name' => $ownerName]);
            $url = $record->getFilamentUrl();

            // Database Notification
            Notification::make()
                ->title($title)
                ->body($body)
                ->icon(\App\Enums\Status::PENDING->getIcon())
                ->color(\App\Enums\Status::PENDING->getColor())
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->label(__('btn.view'))
                        ->url($url),
                ])
                ->sendToDatabase($step->approver);

            // Email Notification
            $message = [
                'subject' => $title,
                'greeting' => __('mail.greeting', ['name' => $step->approver->full_name]),
                'body' => $body,
                'details' => $this->getRecordDetails($record),
                'action' => [
                    'name' => __('btn.decide'), // Changed from 'view' to 'decide'
                    'url' => $url,
                ],
            ];
            $step->approver->notify(new SendEmailNotification($message));
        }
    }

    protected function notifyRecipient($user, $record, string $status, ?string $comment, ?\App\Models\User $actor = null): void
    {
        if (!$user)
            return;

        $isRecipientOwner = $user->id === $record->user_id;

        $modelName = $this->getModelName($record);
        $title = __('msg.label.' . $status, ['label' => '']);

        // Handle special discard body for non-owners (approvers/supervisors)
        if ($status === \App\Enums\Status::DISCARDED->value && !$isRecipientOwner) {
            $body = __('msg.body.discarded_notice', [
                'name' => $record->user?->full_name ?? 'The requester',
                'model' => $modelName,
            ]);
            $url = $record->getFilamentUrl();
        } else {
            // Default generic body
            if ($status === 'partially_approved') {
                $body = __('msg.body.partially_approved', [
                    'model' => $modelName,
                    'name' => $record->pr_no ?? $record->id,
                    'actor' => $actor?->full_name ?? 'Approver',
                ]);
            } else {
                $body = __('msg.body.generic_' . $status, ['model' => $modelName, 'name' => $record->pr_no ?? $record->id]);
            }
            $url = $record->getFilamentUrl();
        }

        // Custom body for LeaveRequest to provide more context
        if ($record instanceof \App\Models\LeaveRequest) {
            $dates = $record->requestDates->sortBy('date');
            $dateString = '';
            if ($dates->isNotEmpty()) {
                $start = \Carbon\Carbon::parse($dates->first()->date)->format('M d, Y');
                $end = \Carbon\Carbon::parse($dates->last()->date)->format('M d, Y');
                $dateString = ($start === $end) ? "on $start" : "from $start to $end";
            }

            $params = [
                'request' => $modelName,
                'days' => $record->days,
                'leave_type' => $record->leaveType->name ?? '-',
                'dates' => $dateString,
                'name' => $actor?->full_name ?? 'Approver',
            ];

            if ($status === \App\Enums\Status::APPROVED->value) {
                $body = __('msg.body.completed_leave_request', $params);
            } elseif ($status === \App\Enums\Status::REJECTED->value) {
                $rejector = $record->approvalSteps()->where('status', \App\Enums\Status::REJECTED)->latest('updated_at')->first()?->approver;
                if ($rejector) {
                    $params['name'] = $rejector->full_name;
                }
                $body = __('msg.body.rejected', $params);
            } elseif ($status === 'partially_approved') {
                $body = __('msg.body.partially_approved', [
                    'model' => $modelName,
                    'name' => $record->id,
                    'actor' => $actor?->full_name ?? 'Approver',
                ]);
            } elseif ($status === \App\Enums\Status::DISCARDED->value) {
                $body = __('msg.body.discarded', $params);
            }
        } elseif ($record instanceof \App\Models\OverTime) {
            $dates = $record->requestDates->sortBy('date');
            $dateString = '';
            if ($dates->isNotEmpty()) {
                $start = \Carbon\Carbon::parse($dates->first()->date)->format('M d, Y');
                $end = \Carbon\Carbon::parse($dates->last()->date)->format('M d, Y');
                $dateString = ($start === $end) ? "on $start" : "from $start to $end";
            }

            $params = [
                'amount' => $record->hours . ' ' . __('field.hour'),
                'date' => $dateString,
                'name' => $actor?->full_name ?? 'Approver',
            ];

            if ($status === \App\Enums\Status::APPROVED->value) {
                $body = __('msg.body.completed_overtime', $params);
            } elseif ($status === \App\Enums\Status::REJECTED->value) {
                $rejector = $record->approvalSteps()->where('status', \App\Enums\Status::REJECTED)->latest('updated_at')->first()?->approver;
                if ($rejector) {
                    $params['name'] = $rejector->full_name;
                }
                $body = __('msg.body.rejected_overtime', $params);
            } elseif ($status === \App\Enums\Status::DISCARDED->value) {
                $body = __('msg.body.discarded_overtime', $params);
            }

        } elseif ($record instanceof \App\Models\WorkFromHome) {
            $start = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('M d, Y') : '-';
            $end = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('M d, Y') : '-';

            $params = [
                'days' => $record->days,
                'from' => $start,
                'to' => $end,
                'name' => $actor?->full_name ?? 'Approver',
            ];

            if ($status === \App\Enums\Status::APPROVED->value) {
                $body = __('msg.body.completed_work_from_home', $params);
            } elseif ($status === \App\Enums\Status::REJECTED->value) {
                $rejector = $record->approvalSteps()->where('status', \App\Enums\Status::REJECTED)->latest('updated_at')->first()?->approver;
                if ($rejector) {
                    $params['name'] = $rejector->full_name;
                }
                $body = __('msg.body.rejected_work_from_home', $params);
            } elseif ($status === \App\Enums\Status::DISCARDED->value) {
                $body = __('msg.body.discarded_work_from_home', $params);
            }

        } elseif ($record instanceof \App\Models\SwitchWorkDay) {
            $start = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('M d, Y') : '-';
            $end = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('M d, Y') : '-';

            $params = [
                'from' => $start,
                'to' => $end,
                'name' => $actor?->full_name ?? 'Approver',
            ];

            if ($status === \App\Enums\Status::APPROVED->value) {
                $body = __('msg.body.completed_switch_working_day', $params);
            } elseif ($status === \App\Enums\Status::REJECTED->value) {
                $rejector = $record->approvalSteps()->where('status', \App\Enums\Status::REJECTED)->latest('updated_at')->first()?->approver;
                if ($rejector) {
                    $params['name'] = $rejector->full_name;
                }
                $body = __('msg.body.rejected_switch_working_day', $params);
            } elseif ($status === \App\Enums\Status::DISCARDED->value) {
                $body = __('msg.body.discarded_switch_working_day', $params);
            }

        } elseif ($record instanceof \App\Models\PurchaseRequest) {
            $params = [
                'number' => $record->pr_no,
                'actionedBy' => $actor?->full_name ?? 'Approver', // Default
            ];

            if ($status === \App\Enums\Status::APPROVED->value) {
                $body = __('msg.body.purchase_request_completed', $params);
            } elseif ($status === \App\Enums\Status::REJECTED->value) {
                $rejector = $record->approvalSteps()->where('status', \App\Enums\Status::REJECTED)->latest('updated_at')->first()?->approver;
                if ($rejector) {
                    $params['actionedBy'] = $rejector->full_name;
                }
                $body = __('msg.body.purchase_request_rejected', $params);
            } elseif ($status === \App\Enums\Status::DISCARDED->value) {
                $body = __('msg.body.purchase_request_discarded', $params);
            }
        }

        $statusEnum = \App\Enums\Status::tryFrom($status);
        $color = $statusEnum?->getColor() ?? 'gray';
        $icon = $statusEnum?->getIcon() ?? 'heroicon-o-information-circle';

        // Database Notification
        Notification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->color($color)
            ->sendToDatabase($user);

        // Email Notification
        $message = [
            'subject' => $title,
            'greeting' => __('mail.greeting', ['name' => $user->full_name]),
            'body' => $body,
            'details' => $this->getRecordDetails($record),
            'action' => [
                'name' => __('btn.check_status'),
                'url' => $url,
            ],
        ];

        $cc = [];
        if ($status === \App\Enums\Status::APPROVED->value) {
            $options = app(SettingOptions::class);
            $ccData = collect($options->cc_emails)->where('model_type', get_class($record))->first();
            if ($ccData && !empty($ccData['accounts'])) {
                $cc = \App\Models\User::whereIn('id', $ccData['accounts'])->pluck('email')->toArray();
            }
        }

        $user->notify(new SendEmailNotification($message, $comment, $cc));
    }

    protected function notifyDiscard($record, ?string $comment, $actor): void
    {
        if (!$record->user)
            return;

        $isOwner = $actor && $actor->id === $record->user_id;

        if ($isOwner) {
            // Requester discarded their own request -> notify CURRENT approver
            $step = $record->currentApprovalStep();
            if ($step && $step->approver) {
                $this->notifyRecipient($step->approver, $record, \App\Enums\Status::DISCARDED->value, $comment, $actor);
            }
        } else {
            // Someone else (approver/admin) discarded -> notify requester
            $this->notifyRecipient($record->user, $record, \App\Enums\Status::DISCARDED->value, $comment, $actor);
        }
    }

    protected function getModelName($record): string
    {
        return match (get_class($record)) {
            \App\Models\LeaveRequest::class => __('model.leave_request'),
            \App\Models\OverTime::class => __('model.overtime'),
            \App\Models\WorkFromHome::class => __('model.work_from_home'),
            \App\Models\PurchaseRequest::class => __('model.purchase_request'),
            \App\Models\SwitchWorkDay::class => __('model.switch_work_day'),
            default => 'Request',
        };
    }

    protected function getRecordDetails($record): array
    {
        $details = [];

        if ($record instanceof \App\Models\LeaveRequest) {
            $details[__('model.leave_type')] = $record->leaveType->name ?? '-';

            $dates = $record->requestDates->sortBy('date');
            if ($dates->isNotEmpty()) {
                $start = \Carbon\Carbon::parse($dates->first()->date)->format('M d, Y');
                $end = \Carbon\Carbon::parse($dates->last()->date)->format('M d, Y');
                $details[__('field.date')] = $start === $end ? $start : "$start - $end";
            }

            $details[__('field.total')] = $record->days . ' ' . __('field.day');
            if (!empty($record->reason)) {
                $details[__('field.reason')] = $record->reason;
            }
        } elseif ($record instanceof \App\Models\OverTime) {
            $details[__('model.overtime')] = $record->hours . ' ' . __('field.hour');

            $dates = $record->requestDates->sortBy('date');
            if ($dates->isNotEmpty()) {
                $start = \Carbon\Carbon::parse($dates->first()->date)->format('M d, Y');
                $end = \Carbon\Carbon::parse($dates->last()->date)->format('M d, Y');
                $details[__('field.date')] = $start === $end ? $start : "$start - $end";
            }
            if (!empty($record->reason)) {
                $details[__('field.reason')] = $record->reason;
            }

        } elseif ($record instanceof \App\Models\WorkFromHome) {
            $start = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('M d, Y') : '-';
            $end = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('M d, Y') : '-';

            $details[__('field.date')] = $start === $end ? $start : "$start - $end";
            $details[__('field.total')] = $record->days . ' ' . __('field.day');
            if (!empty($record->reason)) {
                $details[__('field.reason')] = $record->reason;
            }

        } elseif ($record instanceof \App\Models\SwitchWorkDay) {
            $start = $record->from_date ? \Carbon\Carbon::parse($record->from_date)->format('M d, Y') : '-';
            $end = $record->to_date ? \Carbon\Carbon::parse($record->to_date)->format('M d, Y') : '-';

            $details[__('field.from_date')] = $start;
            $details[__('field.to_date')] = $end;
            if (!empty($record->reason)) {
                $details[__('field.reason')] = $record->reason;
            }

        } elseif ($record instanceof \App\Models\PurchaseRequest) {
            $details[__('field.pr_number')] = $record->pr_no;
            $details[__('field.expected_date')] = $record->expected_date ? \Carbon\Carbon::parse($record->expected_date)->format('M d, Y') : '-';
            $details[__('field.purpose')] = $record->purpose;
            $details[__('field.location')] = $record->location;
        }

        return $details;
    }
}

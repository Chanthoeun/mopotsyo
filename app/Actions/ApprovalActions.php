<?php

namespace App\Actions;

use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\ActionSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ApprovalActions
{
    public static function make(Action|array $action, $alwaysVisibleActions = []): array
    {
        $actions = [
            Action::make('approve')
                ->label(__('btn.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('btn.approve'))
                ->modalDescription(__('btn.msg.approve', ['name' => '']))
                ->action(function (Model $record): void {
                    $step = $record->currentApprovalStep();
                    if ($step) {
                        $step->update([
                            'status' => \App\Enums\Status::APPROVED,
                            'comment' => null,
                            'decided_at' => now(),
                        ]);

                        // Promote next WAITING step to PENDING
                        $nextStep = $record->approvalSteps()
                            ->where('status', \App\Enums\Status::WAITING)
                            ->orderBy('level', 'asc')
                            ->first();

                        if ($nextStep) {
                            $nextStep->update(['status' => \App\Enums\Status::PENDING]);
                        }

                        if ($record->isApproved()) {
                            $record->update(['status' => \App\Enums\Status::APPROVED]);
                        }

                        // Dispatch event (notifies owner or next approver)
                        \App\Events\ApprovalProcessed::dispatch($record, \App\Enums\Status::APPROVED->value, null);

                        Notification::make()
                            ->title(__('msg.label.approved', ['label' => '']))
                            ->success()
                            ->send();
                    }
                })
                ->visible(fn(Model $record) => $record->currentApprovalStep()?->approver_id === Auth::id()),

            Action::make('reject')
                ->label(__('btn.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('btn.reject'))
                ->modalDescription(__('btn.msg.reject', ['name' => '']))
                ->form([
                    Textarea::make('comment')
                        ->label(__('field.remark'))
                        ->required(),
                ])
                ->action(function (Model $record, array $data): void {
                    $step = $record->currentApprovalStep();
                    if ($step) {
                        $step->update([
                            'status' => \App\Enums\Status::REJECTED,
                            'comment' => $data['comment'],
                            'decided_at' => now(),
                        ]);

                        $record->update(['status' => \App\Enums\Status::REJECTED]);

                        // Dispatch event
                        \App\Events\ApprovalProcessed::dispatch($record, \App\Enums\Status::REJECTED->value, $data['comment']);

                        Notification::make()
                            ->title(__('msg.label.rejected', ['label' => '']))
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn(Model $record) => $record->currentApprovalStep()?->approver_id === Auth::id()),

            Action::make('discard')
                ->label(__('btn.discard'))
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('btn.discard'))
                ->modalDescription(__('btn.msg.discard', ['name' => '']))
                ->form([
                    Textarea::make('comment')
                        ->label(__('field.remark'))
                        ->required(),
                ])
                ->action(function (Model $record, array $data): void {
                    $record->update(['status' => \App\Enums\Status::DISCARDED]);

                    // Also mark all pending steps as discarded
                    $record->approvalSteps()->where('status', \App\Enums\Status::PENDING)->update([
                        'status' => \App\Enums\Status::DISCARDED,
                        'comment' => $data['comment'],
                        'decided_at' => now(),
                    ]);

                    // Dispatch event
                    \App\Events\ApprovalProcessed::dispatch($record, \App\Enums\Status::DISCARDED->value, $data['comment']);

                    Notification::make()
                        ->title(__('msg.label.discarded', ['label' => '']))
                        ->danger()
                        ->send();
                })
                ->visible(function (Model $record) {
                    $isRequester = $record->user_id == Auth::id();
                    $isSupervisor = $record->user?->supervisor?->id == Auth::id();

                    if (!(($isRequester || $isSupervisor) && !in_array($record->status, [\App\Enums\Status::DISCARDED, \App\Enums\Status::REJECTED]))) {
                        return false;
                    }

                    // Check if request is expired (in the past)
                    $dateToCheck = null;

                    if ($record instanceof \App\Models\PurchaseRequest) {
                        $dateToCheck = $record->expected_date;
                    } elseif ($record instanceof \App\Models\OverTime) {
                        // For OverTime, check the earliest requested date
                        if ($record->relationLoaded('requestDates')) {
                            $dateToCheck = $record->requestDates->min('date');
                        } else {
                            $dateToCheck = $record->requestDates()->min('date');
                        }
                    } elseif (
                        in_array(get_class($record), [
                            \App\Models\LeaveRequest::class,
                            \App\Models\SwitchWorkDay::class,
                            \App\Models\WorkFromHome::class,
                            \App\Models\Timesheet::class
                        ])
                    ) {
                        $dateToCheck = $record->from_date;
                    }

                    // If no date column found, assume visible (or handle as needed)
                    if (!$dateToCheck) {
                        return true;
                    }

                    // Allow discard if the relevant date is today or in the future
                    return \Illuminate\Support\Carbon::parse($dateToCheck)->startOfDay()->gte(now()->startOfDay());
                }),
        ];

        if (is_array($action)) {
            foreach ($action as $a) {
                $actions[] = $a->visible(fn(Model $record) => $record->isApproved());
            }
        } else {
            $actions[] = $action->visible(fn(Model $record) => $record->isApproved());
        }

        return array_merge($actions, $alwaysVisibleActions);
    }

    public static function makePageActions(\Filament\Actions\Action|array $action, $alwaysVisibleActions = []): array
    {
        $actions = [
            \Filament\Actions\Action::make('approve')
                ->label(__('btn.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('btn.approve'))
                ->modalDescription(__('btn.msg.approve', ['name' => '']))
                ->action(function (Model $record): void {
                    $step = $record->currentApprovalStep();
                    if ($step) {
                        $step->update([
                            'status' => \App\Enums\Status::APPROVED,
                            'comment' => null,
                            'decided_at' => now(),
                        ]);

                        // Promote next WAITING step to PENDING
                        $nextStep = $record->approvalSteps()
                            ->where('status', \App\Enums\Status::WAITING)
                            ->orderBy('level', 'asc')
                            ->first();

                        if ($nextStep) {
                            $nextStep->update(['status' => \App\Enums\Status::PENDING]);
                        }

                        if ($record->isApproved()) {
                            $record->update(['status' => \App\Enums\Status::APPROVED]);
                        }

                        // Dispatch event (notifies owner or next approver)
                        \App\Events\ApprovalProcessed::dispatch($record, \App\Enums\Status::APPROVED->value, null);

                        Notification::make()
                            ->title(__('msg.label.approved', ['label' => '']))
                            ->success()
                            ->send();
                    }
                })
                ->visible(fn(Model $record) => $record->currentApprovalStep()?->approver_id === Auth::id()),

            \Filament\Actions\Action::make('reject')
                ->label(__('btn.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('btn.reject'))
                ->modalDescription(__('btn.msg.reject', ['name' => '']))
                ->form([
                    Textarea::make('comment')
                        ->label(__('field.remark'))
                        ->required(),
                ])
                ->action(function (Model $record, array $data): void {
                    $step = $record->currentApprovalStep();
                    if ($step) {
                        $step->update([
                            'status' => \App\Enums\Status::REJECTED,
                            'comment' => $data['comment'],
                            'decided_at' => now(),
                        ]);

                        $record->update(['status' => \App\Enums\Status::REJECTED]);

                        // Dispatch event
                        \App\Events\ApprovalProcessed::dispatch($record, \App\Enums\Status::REJECTED->value, $data['comment']);

                        Notification::make()
                            ->title(__('msg.label.rejected', ['label' => '']))
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn(Model $record) => $record->currentApprovalStep()?->approver_id === Auth::id()),

            \Filament\Actions\Action::make('discard')
                ->label(__('btn.discard'))
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('btn.discard'))
                ->modalDescription(__('btn.msg.discard', ['name' => '']))
                ->form([
                    Textarea::make('comment')
                        ->label(__('field.remark'))
                        ->required(),
                ])
                ->action(function (Model $record, array $data): void {
                    $record->update(['status' => \App\Enums\Status::DISCARDED]);

                    // Also mark all pending steps as discarded
                    $record->approvalSteps()->where('status', \App\Enums\Status::PENDING)->update([
                        'status' => \App\Enums\Status::DISCARDED,
                        'comment' => $data['comment'],
                        'decided_at' => now(),
                    ]);

                    // Dispatch event
                    \App\Events\ApprovalProcessed::dispatch($record, \App\Enums\Status::DISCARDED->value, $data['comment']);

                    Notification::make()
                        ->title(__('msg.label.discarded', ['label' => '']))
                        ->danger()
                        ->send();
                })
                ->visible(function (Model $record) {
                    $isRequester = $record->user_id == Auth::id();
                    $isSupervisor = $record->user?->supervisor?->id == Auth::id();

                    if (!(($isRequester || $isSupervisor) && !in_array($record->status, [\App\Enums\Status::DISCARDED, \App\Enums\Status::REJECTED]))) {
                        return false;
                    }

                    // Check if request is expired (in the past)
                    $dateToCheck = null;

                    if ($record instanceof \App\Models\PurchaseRequest) {
                        $dateToCheck = $record->expected_date;
                    } elseif ($record instanceof \App\Models\OverTime) {
                        // For OverTime, check the earliest requested date
                        $dateToCheck = $record->requestDates()->min('date');
                    } elseif (
                        in_array(get_class($record), [
                            \App\Models\LeaveRequest::class,
                            \App\Models\SwitchWorkDay::class,
                            \App\Models\WorkFromHome::class,
                            \App\Models\Timesheet::class
                        ])
                    ) {
                        $dateToCheck = $record->from_date;
                    }

                    // If no date column found, assume visible (or handle as needed)
                    if (!$dateToCheck) {
                        return true;
                    }

                    // Allow discard if the relevant date is today or in the future
                    return \Illuminate\Support\Carbon::parse($dateToCheck)->startOfDay()->gte(now()->startOfDay());
                }),
        ];

        if (is_array($action)) {
            foreach ($action as $a) {
                $actions[] = $a->visible(fn(Model $record) => $record->isApproved());
            }
        } else {
            $actions[] = $action->visible(fn(Model $record) => $record->isApproved());
        }

        return array_merge($actions, $alwaysVisibleActions);
    }
}

<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Events\ProcessDiscardedEvent;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

class ViewOverTime extends ViewRecord
{
    use  \EightyNine\Approvals\Traits\HasApprovalHeaderActions;

    protected static string $resource = OverTimeResource::class;

    /**
     * Get the completion action.
     *
     * @return Filament\Actions\Action
     * @throws Exception
     */
    protected function getOnCompletionAction()
    {        
        return Action::make("discard")
            ->label(__('filament-approvals::approvals.actions.discard'))                                       
            ->hidden(fn() => Auth::id() != $this->record->approvalStatus->creator->id || $this->record->isDiscarded())   
            ->icon('heroicon-m-archive-box-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon('heroicon-m-archive-box-x-mark')
            ->form([
                Textarea::make('reason')
                    ->label(__('field.reason'))
                    ->required()
            ])
            ->action(function(array $data){
                $this->record->approvalStatus()->update(['status' => ApprovalStatusEnum::DISCARDED->value]);

                // update approval status
                $approval = ProcessApproval::query()->create([
                    'approvable_type' => $this->record::getApprovableType(),
                    'approvable_id' => $this->record->id,
                    'process_approval_flow_step_id' => null,
                    'approval_action' => ApprovalStatusEnum::DISCARDED,
                    'comment' => $data['reason'],
                    'user_id' => Auth::id(),
                    'approver_name' => Auth::user()->full_name1
                ]);

                ProcessDiscardedEvent::dispatch($approval);

                // notification
                Notification::make()
                    ->success()
                    ->icon('fas-user-clock')
                    ->iconColor('success')
                    ->title(__('msg.label.discarded', ['label' => __('model.overtime')]))
                    ->send();
            });
            
    }
}

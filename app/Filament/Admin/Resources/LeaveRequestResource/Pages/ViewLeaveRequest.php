<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

class ViewLeaveRequest extends ViewRecord
{
    use  \EightyNine\Approvals\Traits\HasApprovalHeaderActions;

    protected static string $resource = LeaveRequestResource::class;

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
            ->action(fn () => $this->record->approvalStatus()->update(['status' => ApprovalStatusEnum::DISCARDED->value]));
            
    }
}

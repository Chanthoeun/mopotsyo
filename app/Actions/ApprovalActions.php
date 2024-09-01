<?php

namespace App\Actions;

use EightyNine\Approvals\Tables\Actions\ApprovalActions as ActionsApprovalActions;
use EightyNine\Approvals\Tables\Actions\ApproveAction;
use EightyNine\Approvals\Tables\Actions\DiscardAction;
use EightyNine\Approvals\Tables\Actions\RejectAction;
use EightyNine\Approvals\Tables\Actions\SubmitAction;
use Filament\Support\Enums\ActionSize;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApprovalActions extends ActionsApprovalActions
{
    public static function make(Action|array $action, $alwaysVisibleActions = []): array
    {
        
        $actions = [
            ActionGroup::make([
                SubmitAction::make(),
                ApproveAction::make()
                    ->visible(fn(Model $record) => Auth::user()->can('approve', $record)),
                DiscardAction::make()
                    ->visible(fn(Model $record) => Auth::user()->can('discard', $record)),
                RejectAction::make()
                    ->visible(fn(Model $record) => Auth::user()->can('reject', $record)),
            ])
                ->label(__('filament-approvals::approvals.actions.approvals'))
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
        ];
        
        if(is_array($action)) {
            foreach($action as $a) {
                $actions[] = $a->visible(fn (Model $record) => $record->isApprovalCompleted());
            }
        } else {
            $actions[] = $action->visible(fn (Model $record) => $record->isApprovalCompleted());
        }
        
        return array_merge($actions, $alwaysVisibleActions);
    }
}

<?php

namespace App\Filament\Admin\Resources\PurchaseRequestResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label(__('btn.submit'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function (PurchaseRequest $record) {
                    $record->submitToApproval();
                    $this->redirect($this->getResource()::getUrl('index'));
                    \Filament\Notifications\Notification::make()
                        ->title(__('msg.body.submitted', ['label' => __('model.purchase_request')]))
                        ->success()
                        ->send();
                })
                ->visible(fn(PurchaseRequest $record) => $record->status === \App\Enums\Status::CREATED),
            Actions\ViewAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

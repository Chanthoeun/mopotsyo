<?php

namespace App\Filament\Admin\Resources\PurchaseRequestResource\Pages;

use App\Filament\Admin\Resources\PurchaseRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseRequest extends ViewRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    public function getSubheading(): ?string
    {
        return __('field.status') . ': ' . $this->record->status->getLabel();
    }

    protected function getHeaderActions(): array
    {
        return \App\Actions\ApprovalActions::makePageActions([], [
            Actions\EditAction::make()
                ->visible(fn(\App\Models\PurchaseRequest $record) => $record->user_id == \Illuminate\Support\Facades\Auth::id() && $record->status === \App\Enums\Status::CREATED),
        ]);
    }
}

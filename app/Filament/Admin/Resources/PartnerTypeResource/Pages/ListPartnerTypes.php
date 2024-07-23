<?php

namespace App\Filament\Admin\Resources\PartnerTypeResource\Pages;

use App\Filament\Admin\Resources\PartnerTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerTypes extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = PartnerTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.partner_type')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

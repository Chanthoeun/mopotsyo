<?php

namespace App\Filament\Admin\Resources\LocationTypeResource\Pages;

use App\Filament\Admin\Resources\LocationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLocationTypes extends ManageRecords
{
    use ManageRecords\Concerns\Translatable;

    protected static string $resource = LocationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.location_type')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

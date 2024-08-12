<?php

namespace App\Filament\Admin\Resources\NationalityResource\Pages;

use App\Filament\Admin\Resources\NationalityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNationalities extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = NationalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()            
                ->label(__('btn.label.new', ['label' => __('model.nationality')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

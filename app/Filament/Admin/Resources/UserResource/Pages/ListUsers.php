<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use JoseEspinal\RecordNavigation\Traits\HasRecordsList;

class ListUsers extends ListRecords
{
    use ListRecords\Concerns\Translatable;
    use HasRecordsList;
 
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.user')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

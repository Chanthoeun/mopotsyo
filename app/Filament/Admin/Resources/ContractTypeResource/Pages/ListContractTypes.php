<?php

namespace App\Filament\Admin\Resources\ContractTypeResource\Pages;

use App\Filament\Admin\Resources\ContractTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractTypes extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = ContractTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.contract_type')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

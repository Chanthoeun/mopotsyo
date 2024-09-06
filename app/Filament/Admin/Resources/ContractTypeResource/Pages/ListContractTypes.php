<?php

namespace App\Filament\Admin\Resources\ContractTypeResource\Pages;

use App\Filament\Admin\Resources\ContractTypeResource;
use App\Imports\ContractTypeImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
            ExcelImportAction::make()
                ->color("primary")
                ->icon('heroicon-o-arrow-up-tray')
                ->use(ContractTypeImport::class),            
            ExportAction::make()
                ->icon('heroicon-o-arrow-down-tray')
                ->exports([
                    ExcelExport::make('table')->fromTable()->withColumns([
                        Column::make('created_at'),
                        Column::make('updated_at'),
                        Column::make('deleted_at'),
                    ])
                ]),
            Actions\LocaleSwitcher::make(),
        ];
    }
}

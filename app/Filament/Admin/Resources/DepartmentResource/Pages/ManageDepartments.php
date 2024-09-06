<?php

namespace App\Filament\Admin\Resources\DepartmentResource\Pages;

use App\Filament\Admin\Resources\DepartmentResource;
use App\Imports\DepartmentImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageDepartments extends ManageRecords
{
    use ManageRecords\Concerns\Translatable;

    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.department')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            ExcelImportAction::make()
                ->color("primary")
                ->icon('heroicon-o-arrow-up-tray')
                ->use(DepartmentImport::class),            
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

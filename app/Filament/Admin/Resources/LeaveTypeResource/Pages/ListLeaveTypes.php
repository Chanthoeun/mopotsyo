<?php

namespace App\Filament\Admin\Resources\LeaveTypeResource\Pages;

use App\Filament\Admin\Resources\LeaveTypeResource;
use App\Imports\LeaveTypeImport;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListLeaveTypes extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.leave_type')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            ExcelImportAction::make()
                ->color("primary")
                ->icon('heroicon-o-arrow-up-tray')
                ->use(LeaveTypeImport::class),            
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

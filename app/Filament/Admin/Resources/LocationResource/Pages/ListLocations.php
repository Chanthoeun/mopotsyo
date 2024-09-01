<?php

namespace App\Filament\Admin\Resources\LocationResource\Pages;

use App\Filament\Admin\Resources\LocationResource;
use App\Imports\LocationsImport;
use App\Models\LocationType;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListLocations extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.location')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            ExcelImportAction::make()
                ->color("primary")
                ->icon('heroicon-o-arrow-up-tray')
                ->use(LocationsImport::class),            
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

    public function getTabs(): array
    {   
        $tabs = array();
        foreach(LocationType::all() as $type){
            $tabs[$type->name] = Tab::make($type->name)
                                    ->modifyQueryUsing(fn (Builder $query) => $query->where('location_type_id', $type->id));
        }

        $tabs['all']    = Tab::make(__('field.all'));

        return $tabs;
    }
}

<?php

namespace App\Filament\Admin\Resources\SwitchWorkDayResource\Pages;

use App\Filament\Admin\Resources\SwitchWorkDayResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListSwitchWorkDays extends ListRecords
{
    protected static string $resource = SwitchWorkDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.request', ['label' => __('model.switch_work_day')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }

    // public function getTabs(): array
    // {   
    //     $tabs = array();        

    //     $tabs['myrequests'] = Tab::make(strtoupper(__('field.label.my', ['label' => __('model.switch_work_days')])))
    //                             ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->where('creator_id', Auth::id())));

    //     $tabs['all']    = Tab::make(strtoupper(__('field.all')))
    //                         ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->whereNot('creator_id', Auth::id())));

    //     return $tabs;
    // }
}

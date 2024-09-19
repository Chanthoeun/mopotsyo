<?php

namespace App\Filament\Admin\Resources\OverTimeResource\Pages;

use App\Filament\Admin\Resources\OverTimeResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListOverTimes extends ListRecords
{
    protected static string $resource = OverTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.request', ['label' => __('model.overtime')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {   
        $tabs = array();        

        $tabs['myrequests'] = Tab::make(strtoupper(__('field.label.my', ['label' => __('model.overtimes')])))
                                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->where('creator_id', Auth::id())));

        $tabs['all']    = Tab::make(strtoupper(__('field.all')))
                            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->whereNot('creator_id', Auth::id())));

        return $tabs;
    }
}

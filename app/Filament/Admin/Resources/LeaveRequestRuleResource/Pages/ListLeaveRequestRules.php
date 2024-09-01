<?php

namespace App\Filament\Admin\Resources\LeaveRequestRuleResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestRuleResource;
use App\Models\LeaveType;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveRequestRules extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = LeaveRequestRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.leave_request_rule')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\LocaleSwitcher::make(),
        ];
    }

    public function getTabs(): array
    {   
        $tabs = array();
        foreach(LeaveType::all() as $type){
            $tabs[$type->abbr] = Tab::make($type->abbr)
                                    ->modifyQueryUsing(fn (Builder $query) => $query->where('leave_type_id', $type->id));
        }

        $tabs['all']    = Tab::make(__('field.all'));

        return $tabs;
    }
}

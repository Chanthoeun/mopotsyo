<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.request', ['label' => __('model.leave')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {   
        $tabs = array();        

        $tabs['myrequests'] = Tab::make(strtoupper(__('field.label.my', ['label' => __('model.leaves')])))
                                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->where('creator_id', Auth::id())));


        // foreach(ApprovalStatusEnum::cases() as $type){
        //     $tabs[$type->name] = Tab::make($type->name)
        //                             ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->where('status', $type->value)));
        // }

        $tabs['all']    = Tab::make(strtoupper(__('field.all')))
                            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->whereIn('creator_id', getSubordinators(Auth::user()))));

        return $tabs;
    }
}

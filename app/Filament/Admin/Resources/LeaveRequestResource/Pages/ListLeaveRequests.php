<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalStatus;

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

        // $leaves = LeaveRequest::whereHas('approvalStatus', fn (Builder $query) => $query->whereJsonContains('steps->role_id', [4]))->toSql();
        $leaves = ProcessApprovalStatus::whereJsonContains('steps->role_id', [3,4,5])->get();
        // dd($leaves);
        // dd(Auth::user()->roles->pluck('id')->toArray());                   
        // foreach(ApprovalStatusEnum::cases() as $type){
        //     $tabs[$type->name] = Tab::make($type->name)
        //                             ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->where('status', $type->value)));
        // }

        
        $tabs['all']    = Tab::make(strtoupper(__('field.all')))
                            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('approvalStatus', fn (Builder $query) => $query->whereNot('creator_id', Auth::id())));

        return $tabs;
    }
}

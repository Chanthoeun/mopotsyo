<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Pages;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        if (!$user)
            return [];

        $subordinateIds = \App\Models\Employee::whereHas('contracts', function (Builder $query) use ($user) {
            $query->where('supervisor_id', $user->id)
                ->where('is_active', true);
        })->pluck('user_id')->toArray();

        $pendingCounts = LeaveRequest::whereIn('user_id', array_merge([$user->id], $subordinateIds))
            ->where('status', \App\Enums\Status::PENDING)
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $myPendingCount = $pendingCounts->get($user->id, 0);
        $subordinatePendingCount = $pendingCounts->forget($user->id)->sum();

        $tabs = [];

        if (LeaveRequest::where('user_id', $user->id)->exists()) {
            $tabs['my_requests'] = Tab::make()
                ->label(__('label.my', ['label' => __('model.leave_request')]))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('user_id', $user->id))
                ->badge($myPendingCount);
        }

        $tabs['my_subordinates'] = Tab::make()
            ->label(__('field.subordinators'))
            ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('user_id', $subordinateIds))
            ->badge($subordinatePendingCount);

        $tabs['all'] = Tab::make()
            ->label(__('field.all'));

        return $tabs;
    }
}

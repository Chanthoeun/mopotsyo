<?php

namespace App\Filament\Admin\Resources\WorkFromHomeResource\Pages;

use App\Filament\Admin\Resources\WorkFromHomeResource;
use App\Models\LeaveRequest;
use App\Models\WorkFromHome;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListWorkFromHomes extends ListRecords
{
    protected static string $resource = WorkFromHomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.request', ['label' => __('model.work_from_home')]))
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

        $pendingCounts = WorkFromHome::whereIn('user_id', array_merge([$user->id], $subordinateIds))
            ->where('status', \App\Enums\Status::PENDING)
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $myPendingCount = $pendingCounts->get($user->id, 0);
        $subordinatePendingCount = $pendingCounts->forget($user->id)->sum();

        return [
            'my_requests' => Tab::make()
                ->label(__('label.my', ['label' => __('model.work_from_home')]))
                ->modifyQueryUsing(fn(Builder $query) => $query->where('user_id', $user->id))
                ->badge($myPendingCount),
            'my_subordinates' => Tab::make()
                ->label(__('field.subordinators'))
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('user_id', $subordinateIds))
                ->badge($subordinatePendingCount),
            'all' => Tab::make()
                ->label(__('field.all')),
        ];
    }
}

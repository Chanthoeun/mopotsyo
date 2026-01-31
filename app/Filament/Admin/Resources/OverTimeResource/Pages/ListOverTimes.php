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
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user)
            return [];

        $subordinateIds = \App\Models\Employee::whereHas('contracts', function (Builder $query) use ($user) {
            $query->where('supervisor_id', $user->id)
                ->where('is_active', true);
        })->pluck('user_id')->toArray();

        $pendingCounts = \App\Models\OverTime::whereIn('user_id', array_merge([$user->id], $subordinateIds))
            ->where('status', \App\Enums\Status::PENDING)
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $myPendingCount = $pendingCounts->get($user->id, 0);
        $subordinatePendingCount = $pendingCounts->forget($user->id)->sum();

        return [
            'my_requests' => Tab::make()
                ->label(__('label.my', ['label' => __('model.overtime')]))
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

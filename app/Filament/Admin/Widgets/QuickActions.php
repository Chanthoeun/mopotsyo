<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Filament\Admin\Resources\SwitchWorkDayResource;
use App\Filament\Admin\Resources\WorkFromHomeResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickActions extends Widget
{
    protected static string $view = 'filament.admin.widgets.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public function getActions(): array
    {
        $actions = [
            [
                'label' => __('btn.create') . ' ' . __('model.leave_request'),
                'icon' => 'heroicon-o-calendar',
                'url' => LeaveRequestResource::getUrl('create'),
                'color' => 'primary',
            ],
            [
                'label' => __('btn.create') . ' ' . __('model.overtime'),
                'icon' => 'heroicon-o-clock',
                'url' => OverTimeResource::getUrl('create'),
                'color' => 'warning',
            ],
            [
                'label' => __('btn.create') . ' ' . __('model.work_from_home'),
                'icon' => 'heroicon-o-home',
                'url' => WorkFromHomeResource::getUrl('create'),
                'color' => 'success',
            ],
        ];

        // Add Switch Work Day only for Consultants
        $user = Auth::user();
        if ($user && $user->employee_type === 'consultant') {
            $actions[] = [
                'label' => __('btn.create') . ' ' . __('model.switch_work_day'),
                'icon' => 'heroicon-o-arrow-path-rounded-square',
                'url' => SwitchWorkDayResource::getUrl('create'),
                'color' => 'info',
            ];
        }

        return $actions;
    }
}

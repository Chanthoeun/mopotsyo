<?php

namespace App\Filament\Admin\Resources\LeaveCarryForwardResource\Pages;

use App\Filament\Admin\Resources\LeaveCarryForwardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveCarryForwards extends ListRecords
{
    protected static string $resource = LeaveCarryForwardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('btn.label.new', ['label' => __('model.carry_forward')]))
                ->color('primary')
                ->icon('heroicon-o-plus'),
            Actions\Action::make('generate_carry_forward')
                ->label(__('btn.generate_carry_forward'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('btn.generate_carry_forward'))
                ->modalDescription(__('btn.msg.generate_carry_forward'))
                ->visible(fn() => auth()->user()->hasRole(['super_admin', 'human_resource']))
                ->action(function () {
                    $count = 0;
                    // Find expired entitlements that allow carry forward and have remaining balance
                    $expiredEntitlements = \App\Models\LeaveEntitlement::where('end_date', '<', now())
                        ->whereHas('leaveType', function ($query) {
                        $query->where('option->allow_carry_forward', true);
                    })
                        ->get();

                    foreach ($expiredEntitlements as $expiredEntitlement) {
                        if ($expiredEntitlement->remaining <= 0)
                            continue;

                        // Check if there is a NEW entitlement that starts after the expired one ends
                        $newEntitlement = \App\Models\LeaveEntitlement::where('user_id', $expiredEntitlement->user_id)
                            ->where('leave_type_id', $expiredEntitlement->leave_type_id)
                            ->where('start_date', '>', $expiredEntitlement->end_date)
                            ->orderBy('start_date', 'asc')
                            ->first();

                        // If no new entitlement exists, skip generation (employee might have resigned or not renewed)
                        if (!$newEntitlement)
                            continue;

                        // Check if the NEW entitlement already has a linked carry forward record
                        // We link the CF to the NEW entitlement to show it belongs to the new period
                        $exists = \App\Models\LeaveCarryForward::where('leave_entitlement_id', $newEntitlement->id)->exists();
                        if ($exists)
                            continue;

                        $duration = $expiredEntitlement->leaveType->option['carry_forward_duration'] ?? '+3 months'; // default fallback
        
                        // Calculate End Date based on the NEW entitlement's start date
                        try {
                            $endDate = \Carbon\Carbon::parse($newEntitlement->start_date)->modify($duration);
                        } catch (\Exception $e) {
                            $endDate = \Carbon\Carbon::parse($newEntitlement->start_date)->addMonths(3);
                        }

                        \App\Models\LeaveCarryForward::create([
                            'user_id' => $newEntitlement->user_id,
                            'leave_entitlement_id' => $newEntitlement->id, // Link to the NEW entitlement
                            'start_date' => $newEntitlement->start_date,
                            'end_date' => $endDate,
                            'balance' => $expiredEntitlement->remaining, // Loop balance from OLD entitlement
                        ]);
                        $count++;
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title(__('msg.label.success', ['label' => __('btn.generate_carry_forward')]))
                        ->body("Successfully generated {$count} carry forward records.")
                        ->send();
                }),
            Actions\Action::make('link_leave_request')
                ->label(__('btn.link_leave_request'))
                ->icon('heroicon-o-link')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('btn.link_leave_request'))
                ->modalDescription(__('btn.msg.link_leave_request'))
                ->visible(fn() => auth()->user()->hasRole(['super_admin', 'human_resource']))
                ->action(function () {
                    $totalLinked = 0;
                    $records = \App\Models\LeaveCarryForward::where('end_date', '>=', now())->get();

                    foreach ($records as $record) {
                        $leaveType = $record->leaveEntitlement->leaveType;

                        // Find eligible LeaveRequest IDs
                        $leaveRequestIds = \App\Models\LeaveRequest::where('user_id', $record->user_id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('status', \App\Enums\Status::APPROVED->value)
                            ->pluck('id');

                        // Find eligible RequestDates
                        $dates = \App\Models\RequestDate::where('requestdateable_type', \App\Models\LeaveRequest::class)
                            ->whereIn('requestdateable_id', $leaveRequestIds)
                            ->whereBetween('date', [$record->start_date, $record->end_date])
                            ->whereNull('leave_carry_forward_id')
                            ->get();

                        $remaining = $record->remaining;
                        $linkedInThisRecord = 0;

                        foreach ($dates as $date) {
                            $dayLength = app(\App\Settings\SettingWorkingHours::class)->day ?: 8;
                            $days = $date->hours / $dayLength;

                            if ($remaining >= $days) {
                                $date->leaveCarryForward()->associate($record)->save();
                                $remaining -= $days;
                                $linkedInThisRecord++;
                            }
                        }
                        $totalLinked += $linkedInThisRecord;
                    }

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title(__('msg.label.success', ['label' => __('btn.link_leave_request')]))
                        ->body("Successfully linked {$totalLinked} request dates.")
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make(__('field.all')),
            'active' => \Filament\Resources\Components\Tab::make(__('field.active'))
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('end_date', '>=', now())),
            'inactive' => \Filament\Resources\Components\Tab::make(__('field.inactive'))
                ->modifyQueryUsing(fn(\Illuminate\Database\Eloquent\Builder $query) => $query->where('end_date', '<', now())),
        ];
    }
}

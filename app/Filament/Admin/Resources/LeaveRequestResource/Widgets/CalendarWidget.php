<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Widgets;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Filament\Admin\Resources\OverTimeResource;
use App\Filament\Admin\Resources\WorkFromHomeResource;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    // Disable all modal interactions
    public static function canView(): bool
    {
        return true; // Can view the calendar
    }

    public static function canCreate(): bool
    {
        return false; // Cannot create via modal
    }

    public static function canEdit(): bool
    {
        return false; // Cannot edit via modal
    }

    public static function canDelete(): bool
    {
        return false; // Cannot delete via modal
    }

    public function getCreateEventModalFormSchema(): array
    {
        return []; // Empty form schema
    }

    public function getEditEventModalFormSchema(): array
    {
        return []; // Empty form schema
    }

    public function authorize($ability, $arguments = []): bool
    {
        return false; // Block all modal authorization
    }

    // Override to prevent modal from opening on select
    public function onEventSelect($info): void
    {
        // Do nothing - prevents modal from opening
        return;
    }

    public function onEventClick($info): void
    {
        // Only allow viewing existing events via URL
        if (!empty($info['event']['url'])) {
            $this->redirect($info['event']['url']);
        }
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start']);
        $end = Carbon::parse($fetchInfo['end']);

        $events = [];

        $query = LeaveRequest::whereIn('status', [\App\Enums\Status::APPROVED, \App\Enums\Status::PENDING])
            ->with(['requestDates', 'leaveType', 'user'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('from_date', [$start, $end])
                    ->orWhereBetween('to_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('from_date', '<=', $start)
                            ->where('to_date', '>=', $end);
                    });
            });

        // Apply strict scoping if not Admin/HR
        if (!auth()->user()->hasRole(['super_admin', 'human_resource'])) {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhereHas('approvalSteps', fn($sub) => $sub->where('approver_id', auth()->id()));
            });
        }

        $leaveRequests = $query->get();

        foreach ($leaveRequests as $leaveRequest) {
            foreach ($leaveRequest->requestDates as $requestDate) {
                $date = Carbon::parse($requestDate->date);
                if ($date->between($start, $end)) {
                    $isPending = $leaveRequest->status === \App\Enums\Status::PENDING;
                    $titlePrefix = $isPending ? '(Pending) ' : '';
                    $color = $isPending ? '#gray' : $leaveRequest->leaveType->color; // Use gray or warning color for pending

                    // Or keep original color but mark title
                    // Let's stick to title change and maybe opacity if possible, but fullcalendar simpler with color.
                    // If we want to keep Leave Type distinction even when pending, we should just change title.
                    // Let's just use the Type Color but add text indiciation.

                    $events[] = collect(EventData::make()
                        ->id($leaveRequest->id)
                        ->title($titlePrefix . $leaveRequest->leaveType->abbr . ' - ' . ($leaveRequest->user->name ?? 'Unknown') . ' - ' . trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days]))
                        ->start($date)
                        ->end($date->copy()->addDay())
                        ->allDay(true)
                        ->backgroundColor($leaveRequest->leaveType->color)
                        ->borderColor($isPending ? 'orange' : $leaveRequest->leaveType->color) // valid enough distinction?
                        ->url(LeaveRequestResource::getUrl('view', ['record' => $leaveRequest]), true))->toArray();
                }
            }
        }

        $publicHolidays = PublicHoliday::whereBetween('date', [$start, $end])->get();
        foreach ($publicHolidays as $holiday) {
            $events[] = collect(EventData::make()
                ->id($holiday->id)
                ->title($holiday->name)
                ->start(Carbon::parse($holiday->date))
                ->end(Carbon::parse($holiday->date))
                ->allDay(true)
                ->backgroundColor('red'))->toArray();
        }

        return $events;
    }

    public function eventDidMount(): string
    {
        return <<<JS
        function({ event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view }){
            el.setAttribute("x-tooltip", "tooltip");
            el.setAttribute("x-data", "{ tooltip: '"+event.title+"' }");
        }
    JS;
    }

    public function viewDidMount(): string
    {
        return <<<JS
        function() {
            // Immediately close any modal that opens
            document.addEventListener('DOMContentLoaded', function() {
                const observer = new MutationObserver(function(mutations) {
                    const modal = document.querySelector('[x-on\\\\:click.away]');
                    if (modal && modal.textContent.includes('Create')) {
                        const closeButton = modal.querySelector('[aria-label="Close"]');
                        if (closeButton) {
                            closeButton.click();
                        }
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            });
        }
        JS;
    }

    public function config(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth',
            ],
            'editable' => false,
            'selectable' => false,
            'selectMirror' => false,
            'select' => <<<JS
                function(info) {
                    // Prevent default modal - do nothing
                    info.view.calendar.unselect();
                    return false;
                }
            JS,
            // Temporarily disabled until modal issue is resolved
            // Use Quick Actions buttons instead
            /*
            'dateClick' => <<<JS
                function(info) {
                    const date = info.dateStr;
                    const leaveUrl = '/admin/leave-requests/create?from_date=' + date + '&to_date=' + date;
                    const overtimeUrl = '/admin/over-times/create?from_date=' + date + '&to_date=' + date;
                    const wfhUrl = '/admin/work-from-homes/create?from_date=' + date + '&to_date=' + date;

                    const choice = confirm('Create Leave Request? (OK)\\n' + 
                                          'Or Cancel to choose Overtime/WFH');

                    if (choice) {
                        window.location.href = leaveUrl;
                    } else {
                        const secondChoice = confirm('Create Overtime? (OK)\\nCreate Work From Home? (Cancel)');
                        if (secondChoice) {
                            window.location.href = overtimeUrl;
                        } else {
                            window.location.href = wfhUrl;
                        }
                    }
                }
            JS,
            */
        ];
    }
}

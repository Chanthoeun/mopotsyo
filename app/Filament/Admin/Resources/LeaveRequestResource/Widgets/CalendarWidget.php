<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Widgets;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{

    public function fetchEvents(array $fetchInfo): array
    {
        $start = Carbon::parse($fetchInfo['start']);
        $end = Carbon::parse($fetchInfo['end']);

        $events = [];

        $leaveRequests = LeaveRequest::approved()
            ->with(['requestDates', 'leaveType', 'approvalStatus.creator'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('from_date', [$start, $end])
                    ->orWhereBetween('to_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('from_date', '<=', $start)
                            ->where('to_date', '>=', $end);
                    });
            })
            ->get();

        foreach ($leaveRequests as $leaveRequest) {
            foreach ($leaveRequest->requestDates as $requestDate) {
                $date = Carbon::parse($requestDate->date);
                if ($date->between($start, $end)) {
                    $events[] = collect(EventData::make()
                        ->id($leaveRequest->id)
                        ->title($leaveRequest->leaveType->abbr . ' - ' . $leaveRequest->approvalStatus->creator->name . ' - ' . trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days]))
                        ->start($date)
                        ->end($date->copy()->addDay())
                        ->allDay(true)
                        ->backgroundColor($leaveRequest->leaveType->color)
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
}

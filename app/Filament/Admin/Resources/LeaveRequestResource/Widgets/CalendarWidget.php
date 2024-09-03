<?php

namespace App\Filament\Admin\Resources\LeaveRequestResource\Widgets;

use App\Filament\Admin\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $fetchInfo): array
    {
        // You can use $fetchInfo to filter events by date.
        // This method should return an array of event-like objects. See: https://github.com/saade/filament-fullcalendar/blob/3.x/#returning-events
        // You can also return an array of EventData objects. See: https://github.com/saade/filament-fullcalendar/blob/3.x/#the-eventdata-class
        $events = array();
        $events = LeaveRequest::approved()->get()->map(fn($leaveRequest) => EventData::make()
            ->id($leaveRequest->id)
            ->title($leaveRequest->leaveType->abbr .' - '. $leaveRequest->user->name .' - '. trans_choice('field.days_with_count', $leaveRequest->days, ['count' => $leaveRequest->days]))
            ->start($leaveRequest->start_date)
            ->end($leaveRequest->end_date)
            ->allDay(true)
            ->backgroundColor($leaveRequest->leaveType->color)
            ->url(LeaveRequestResource::getUrl('view', ['record' => $leaveRequest]))
        );

        $events = PublicHoliday::all()->map(fn($holiday) => EventData::make()
            ->id($holiday->id)
            ->title($holiday->name)
            ->start($holiday->date)
            ->end($holiday->date)
            ->allDay(true)
            ->backgroundColor('red')
        )->toArray();
        
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

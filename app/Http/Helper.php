<?php

// use App\Enums\ActionStatusEnum;
// use App\Enums\StatusEnum;
// use App\Enums\TripProgressEnum;
// use App\Models\HR\Employee;
// use App\Models\User;

use App\Models\EmployeeContract;
use App\Models\LeaveRequest;
use App\Models\ProcessApprover;
use App\Models\PublicHoliday;
use App\Models\PurchaseRequest;
use App\Models\RequestDate;
use App\Models\User;
use App\Settings\SettingOptions;
use App\Settings\SettingWorkingHours;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Support\Collection;

if (!function_exists('getSubordinators')) {
    function getSubordinators(User $user)
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('employee.contracts', function (Builder $query) use ($user) {
                $query->where('is_active', true)
                    ->where(function (Builder $q) use ($user) {
                        $q->where('supervisor_id', $user->id)
                            ->orWhereIn('department_id', $user->departments->pluck('id'));
                    });
            })
            ->pluck('id');
    }
}

if (!function_exists('getAcronym')) {
    function getAcronym($string)
    {
        $words = explode(" ", $string);
        $acronym = "";

        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
        }

        $acronym = preg_replace('/[^A-Za-z0-9\-]/', '', $acronym); // Removes special chars.
        return $acronym;
    }
}

if (!function_exists('decimalToTime')) {
    /**
     * Convert decimal time into time in the format hh:mm:ss
     *
     * @param integer The time as a decimal value.
     *
     * @return string $time The converted time value.
     */
    function decimalToTime($decimal)
    {
        $h = intval($decimal);
        $m = round(($decimal - $h) * 60);

        if ($m >= 60) {
            $h++;
            $m = 0;
        }

        if ($m == 0) {
            return sprintf("%dh", $h);
        }

        return sprintf("%dh : %02dmin", $h, $m);
    }
}

if (!function_exists('getHoursBetweenTwoTimes')) {
    function getHoursBetweenTwoTimes($stat_time, $end_time, $break_time = 0, $date = null): float
    {
        if ($date != null) {
            $stat_time = Carbon::parse($date)->format('Y-m-d') . ' ' . $stat_time;
            $end_time = Carbon::parse($date)->format('Y-m-d') . ' ' . $end_time;
        } else {
            $stat_time = now()->format('Y-m-d') . ' ' . $stat_time;
            $end_time = now()->format('Y-m-d') . ' ' . $end_time;
        }
        $startTime = Carbon::parse($stat_time)->timezone(config('app.timezone'));
        $endTime = Carbon::parse($end_time)->timezone(config('app.timezone'));
        $hours = $startTime->floatDiffInHours($endTime);

        if (!empty($break_time) && $hours > 4) {
            return floatval($hours - $break_time);
        }

        return floatval($hours);
    }
}

if (!function_exists('getEntitlementBalance')) {
    function getEntitlementBalance($jointDate, $leaveType): int
    {
        $startDate = Carbon::parse($jointDate);
        $endDate = Carbon::createFromDate(now()->year, $startDate->month, $startDate->day);
        $duration = intval($startDate->diffInYears($endDate));
        $increment = 0;

        if (!empty($leaveType->option) && !empty($leaveType->option['balance_increment_amount']) && !empty($leaveType->option['balance_increment_period'])) {
            $increment = intval($duration / floatval($leaveType->option['balance_increment_period']));
        }

        $balance = intval($leaveType->balance + $increment);

        if (!empty($leaveType->maximum_balance) && $leaveType->maximum_balance > $balance) {
            return $balance;
        }

        return $leaveType->maximum_balance;
    }
}

if (!function_exists('getDayOfWeek')) {
    function getDayOfWeek($date): int
    {
        return Carbon::parse($date)->dayOfWeek();
    }
}

if (!function_exists('isWorkHour')) {
    function isWorkHour($user, $date, $request_time): bool
    {
        if (publicHoliday($date)) {
            return false;
        }

        $requestTime = Carbon::parse($request_time);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek();

        // Access workDays through the employee relationship
        if (!$user->employee) {
            return false;
        }

        if (!$user->employee->relationLoaded('workDays')) {
            $user->employee->load('workDays');
        }

        foreach ($user->employee->workDays->where('is_active', true) as $workDay) {
            if ($workDay->day_name->value != $dayOfWeek) {
                continue;
            }

            $startWorkHour = Carbon::parse($workDay->start_time);
            $endWorkHour = Carbon::parse($workDay->end_time);

            if ($requestTime->isBetween($startWorkHour, $endWorkHour, true)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('getDayName')) {
    function getDayName($date)
    {
        return Carbon::parse($date)->locale(config('app.locale'))->dayName;
    }
}

if (!function_exists('weekend')) {
    function weekend($date): bool|string
    {
        $date = Carbon::parse($date);
        if ($date->isWeekend()) {
            return $date->locale(config('app.locale'))->dayName;
        }
        return false;
    }
}
if (!function_exists('publicHoliday')) {
    function publicHoliday($date): bool|object
    {
        static $holidays = null;
        if ($holidays === null) {
            $holidays = PublicHoliday::all()->keyBy(fn($h) => Carbon::parse($h->date)->toDateString());
        }
        $dateStr = ($date instanceof Carbon) ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return $holidays->get($dateStr) ?? false;
    }
}

if (!function_exists('getDateRangeBetweenTwoDates')) {
    function getDateRangeBetweenTwoDates($startDate, $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        return $period->toArray();
    }
}

if (!function_exists('dateIsNotDuplicated')) {
    function dateIsNotDuplicated($user, $date)
    {
        return !RequestDate::query()
            ->where('requestdateable_type', 'App\Models\LeaveRequest')
            ->whereHas('requestdateable', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'approved']);
            })
            ->whereDate('date', $date)
            ->exists();
    }
}

if (!function_exists('getLeaveDuplicatedDate')) {
    function getLeaveDuplicatedDate($user, $date)
    {
        return RequestDate::query()
            ->where('requestdateable_type', 'App\Models\LeaveRequest')
            ->whereHas('requestdateable', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'approved']);
            })
            ->whereDate('date', $date)
            ->first();
    }
}



if (!function_exists('calculateAccrud')) {
    function calculateAccrud($balance, $startDate, $endDate): float
    {
        $from = Carbon::parse($startDate, config('app.timezone'));
        $to = Carbon::parse($endDate, config('app.timezone'));
        $days = $from->diffInDays($to);

        if ($days > 0) {
            $perDay = ($balance / getDaysOfTheYear(now()->year));
            $accrued = round(($days * $perDay), 2);
            return $accrued;
        }
        return 0;
    }
}

if (!function_exists('getDaysOfTheYear')) {
    function getDaysOfTheYear($year)
    {
        $startDateOfTheYear = Carbon::createFromDate($year, 1, 1);
        $startDateOfNextYear = Carbon::createFromDate($year + 1, 1, 1);
        return $startDateOfTheYear->diffInDays($startDateOfNextYear);
    }
}

if (!function_exists('getDaysFromHours')) {
    function getDaysFromHours($user_id, $hours): float
    {
        $dayLength = app(SettingWorkingHours::class)->day ?: 8;
        return round($hours / $dayLength, 1);
    }
}

if (!function_exists('getRequestDays')) {
    function getRequestDays($requestDates)
    {
        if (empty($requestDates)) {
            return 0.0;
        }

        $requestDays = 0;
        foreach ($requestDates as $requestDate) {
            $requestDays += $requestDate['hours'] ?? 0;
        }

        $dayLength = app(SettingWorkingHours::class)->day ?: 8;

        return floatval($requestDays / $dayLength);
    }
}

if (!function_exists('getOvertimeDays')) {
    function getOvertimeDays($user, $overtimeIds = null)
    {
        if ($overtimeIds == null) {
            $overtimes = $user->overtimes()->whereDate('expiry_date', '>=', now())->where('unused', true)->get();
        } else {
            $overtimes = $user->overtimes()->whereIn('id', $overtimeIds)->whereDate('expiry_date', '>=', now())->where('unused', true)->get();
        }

        $overtimeHours = 0;
        foreach ($overtimes as $overtime) {
            $overtimeHours += $overtime->hours;
        }
        return floatval($overtimeHours / app(SettingWorkingHours::class)->day);
    }
}



if (!function_exists('isRequestBackDate')) {
    function isRequestBackDate($date)
    {
        $date = Carbon::parse($date);
        if ($date < now()->toDateString()) {
            return true;
        }
        return false;
    }
}



// if(!function_exists('rangeWeek')){
//     function rangeWeek ($date = false) {
//         date_default_timezone_set (date_default_timezone_get());
//         $dt = strtotime($date == false ? date('Y-m-d') : $date);
//         $startDate = date ('N', $dt) == 1 ? date ('Y-m-d', $dt) : date ('Y-m-d', strtotime ('last monday', $dt));
//         $endDate = date('N', $dt) == 7 ? date ('Y-m-d', $dt) : date ('Y-m-d', strtotime ('next sunday', $dt));

//         return getDateRangeBetweenTwoDates($startDate, $endDate);
//     }
// }

// if(!function_exists('rangeMonth')){
//     function rangeMonth ($date = false) {
//         date_default_timezone_set (date_default_timezone_get());
//         $dt = strtotime($date == false ? date('Y-m-d') : $date);
//         $firstDate = date ('Y-m-d', strtotime ('first day of this month', $dt));
//         $lastDate = date ('Y-m-d', strtotime ('last day of this month', $dt));

//         return getDateRangeBetweenTwoDates($firstDate, $lastDate);
//     }
// }




if (!function_exists('isOnLeave')) {
    function isOnLeave($user, $date): bool|object
    {
        static $results = [];
        $userId = $user->id;
        if (!isset($results[$userId])) {
            $results[$userId] = RequestDate::with('requestdateable.leaveType')
                ->where('requestdateable_type', 'App\Models\LeaveRequest')
                ->whereHas('requestdateable', function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                    $query->whereIn('status', ['approved', 'pending', 'waiting']);
                })->get()->keyBy(fn($rd) => Carbon::parse($rd->date)->toDateString());
        }
        $dateStr = ($date instanceof Carbon) ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return $results[$userId]->get($dateStr) ?? false;
    }
}

if (!function_exists('isOvertime')) {
    function isOvertime($user, $date): bool|object
    {
        static $results = [];
        $userId = $user->id;
        if (!isset($results[$userId])) {
            $results[$userId] = RequestDate::with('requestdateable')
                ->where('requestdateable_type', 'App\Models\OverTime')
                ->whereHas('requestdateable', function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                    $query->whereIn('status', ['pending', 'approved', 'waiting']);
                })->get()->keyBy(fn($rd) => Carbon::parse($rd->date)->toDateString());
        }
        $dateStr = ($date instanceof Carbon) ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return $results[$userId]->get($dateStr) ?? false;
    }
}

if (!function_exists('isWorkFromHome')) {
    function isWorkFromHome($user, $date): bool|object
    {
        static $results = [];
        $userId = $user->id;
        if (!isset($results[$userId])) {
            $results[$userId] = RequestDate::with('requestdateable')
                ->where('requestdateable_type', 'App\Models\WorkFromHome')
                ->whereHas('requestdateable', function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                    $query->whereIn('status', ['pending', 'approved', 'waiting']);
                })->get()->keyBy(fn($rd) => Carbon::parse($rd->date)->toDateString());
        }
        $dateStr = ($date instanceof Carbon) ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return $results[$userId]->get($dateStr) ?? false;
    }
}

if (!function_exists('isWorkDay')) {
    function isWorkDay($user, $date): bool|object
    {
        // Access workDays through the employee relationship
        if (!$user->employee) {
            return false;
        }

        if (!$user->employee->relationLoaded('workDays')) {
            $user->employee->load('workDays');
        }

        return $user->employee->workDays->where('is_active', true)->where('day_name.value', getDayOfWeek($date))->first() ?? false;
    }
}

if (!function_exists('isSwitchWorkDay')) {
    function isSwitchWorkDay($user, $date): bool|object
    {
        static $results = [];
        $userId = $user->id;
        if (!isset($results[$userId])) {
            $results[$userId] = $user->switchWorkDays()
                ->whereIn('status', ['approved', 'pending', 'waiting'])
                ->get()
                ->keyBy(fn($swd) => Carbon::parse($swd->from_date)->toDateString());
        }
        $dateStr = ($date instanceof Carbon) ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return $results[$userId]->get($dateStr) ?? false;
    }
}

if (!function_exists('isSwitchWorkDayToDate')) {
    function isSwitchWorkDayToDate($user, $date): bool|object
    {
        static $results = [];
        $userId = $user->id;
        if (!isset($results[$userId])) {
            $results[$userId] = $user->switchWorkDays()
                ->whereIn('status', ['approved', 'pending', 'waiting'])
                ->get()
                ->keyBy(fn($swd) => Carbon::parse($swd->to_date)->toDateString());
        }
        $dateStr = ($date instanceof Carbon) ? $date->toDateString() : Carbon::parse($date)->toDateString();
        return $results[$userId]->get($dateStr) ?? false;
    }
}

if (!function_exists('getTakenLeave')) {
    function getTakenLeave($user, $leaveType, $from_date = null, $to_date = null, $includeCF = false): float
    {
        if (!$from_date || !$to_date) {
            $entitlement = $user->entitlements()
                ->where('leave_type_id', $leaveType)
                ->where('is_active', true)
                ->latest('start_date')
                ->first();

            if (!$entitlement) {
                return 0.0;
            }

            $from_date = $entitlement->start_date;
            $to_date = $entitlement->end_date;
        }

        // Optimized: Sum hours directly from RequestDate using a single database query
        $totalHours = RequestDate::query()
            ->whereHasMorph('requestdateable', [LeaveRequest::class], function ($query) use ($user, $leaveType) {
                $query->where('user_id', $user->id)
                    ->where('leave_type_id', $leaveType)
                    ->whereIn('status', ['approved', 'pending', 'waiting']);
            })
            ->whereBetween('date', [$from_date, $to_date])
            ->when(!$includeCF, function ($query) {
                $query->whereNull('leave_carry_forward_id');
            })
            ->sum('hours');

        $dayLength = app(SettingWorkingHours::class)->day ?: 8;

        return floatval($totalHours / $dayLength);
    }
}

if (!function_exists('getCarryForwardTaken')) {
    function getCarryForwardTaken($carryForward, $from_date, $to_date)
    {
        $carryForwardHours = $carryForward->requestDates()
            ->whereHasMorph('requestdateable', [LeaveRequest::class], function ($query) {
                $query->whereIn('status', [
                    \App\Enums\Status::APPROVED,
                    \App\Enums\Status::PENDING,
                    \App\Enums\Status::WAITING,
                ]);
            })
            ->whereBetween('date', [$from_date, $to_date])
            ->sum('hours');

        $dayLength = app(SettingWorkingHours::class)->day ?: 8;

        return floatval($carryForwardHours / $dayLength);
    }
}

if (!function_exists('generatePrNo')) {
    function generatePrNo()
    {
        $prefix = 'PR' . date('Ym') . '-';
        $count = \App\Models\PurchaseRequest::count() + 1;

        return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
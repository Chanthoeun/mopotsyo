<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SettingWorkingHours extends Settings
{

    public ?int $day;
    public ?int $week;
    public ?int $break_time;
    public ?string $break_from;
    public ?string $break_to;
    public ?array $work_days;

    public static function group(): string
    {
        return 'workhour';
    }
}
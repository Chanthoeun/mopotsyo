<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SettingWorkingHours extends Settings
{

    public ?int $day;
    public ?int $week;
    public ?array $work_days;

    public static function group(): string
    {
        return 'workhour';
    }
}
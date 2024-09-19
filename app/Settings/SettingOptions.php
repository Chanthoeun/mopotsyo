<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SettingOptions extends Settings
{
    public ?bool $allow_add_user;
    public ?bool $allow_work_from_home;
    public ?bool $allow_switch_day_work;
    public ?bool $allow_overtime;
    public ?int $overtime_expiry;
    public ?int $overtime_link;
    public ?array $cc_emails;

    public static function group(): string
    {
        return 'option';
    }
}
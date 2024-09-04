<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SettingGeneral extends Settings
{
    public ?string $organization;
    public ?string $abbr;
    public ?string $telephone;
    public ?string $email;
    public ?string $website;
    public ?string $address;
    public ?string $logo;

    public static function group(): string
    {
        return 'general';
    }
}
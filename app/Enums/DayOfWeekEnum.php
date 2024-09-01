<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DayOfWeekEnum: int implements HasColor, HasIcon, HasLabel
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 0;


    public static function fromString(string $value): self
    {
        return match($value) {
            __('field.days.monday')    => self::MONDAY,
            __('field.days.tuesday')   => self::TUESDAY,
            __('field.days.wednesday') => self::WEDNESDAY,
            __('field.days.thursday')  => self::THURSDAY,
            __('field.days.friday')    => self::FRIDAY,
            __('field.days.saturday')  => self::SATURDAY,
            __('field.days.sunday')    => self::SUNDAY
        };
    }

    public function getLabel(): ?string
    {        
        return match ($this) {
            self::MONDAY        => __('field.days.monday'),
            self::TUESDAY       => __('field.days.tuesday'),
            self::WEDNESDAY     => __('field.days.wednesday'),
            self::THURSDAY      => __('field.days.thursday'),
            self::FRIDAY        => __('field.days.friday'),
            self::SATURDAY      => __('field.days.saturday'),
            self::SUNDAY        => __('field.days.sunday'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::MONDAY        => 'primary',
            self::TUESDAY       => 'primary',
            self::WEDNESDAY     => 'primary',
            self::THURSDAY      => 'primary',
            self::FRIDAY        => 'primary',
            self::SATURDAY      => 'danger',
            self::SUNDAY        => 'danger'
            
        };
    }

    public function getIcon(): ?string
    {
        return 'fas-calendar-day';
        // return match ($this) {
        //     self::MONDAY    => 'fas-calendar-day',
        //     self::TUESDAY   => 'fas-calendar-day',
        //     self::WEDNESDAY => 'fas-calendar-day',
        //     self::THURSDAY  => 'fas-calendar-day',
        //     self::FRIDAY    => 'fas-calendar-day',
        //     self::SATURDAY  => 'fas-calendar-day',
        //     self::SUNDAY    => 'fas-calendar-day'
        // };
    }
}

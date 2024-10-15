<?php
  
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TimesheetTypeEnum: int implements HasLabel, HasColor, HasIcon
{
    case OFFICE     = 1;
    case HOME       = 2;    
    case FIELD      = 3;    
    case HOLIDAY    = 4;    
    case LEAVE      = 5;  
    case WEEKEND    = 6;  
    case NOT_WORK    = 7;
    case OVERTIME    = 8;

    public function getLabel(): ?string
    {        
        return match ($this) {
            self::OFFICE   => __('field.timesheet_type.office'),
            self::HOME     => __('field.timesheet_type.home'),
            self::FIELD    => __('field.timesheet_type.field'),
            self::HOLIDAY  => __('field.timesheet_type.holiday'),
            self::LEAVE    => __('field.timesheet_type.leave'),
            self::WEEKEND    => __('field.timesheet_type.weekend'),
            self::NOT_WORK    => __('field.timesheet_type.not_work'),
            self::OVERTIME    => __('field.timesheet_type.overtime'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::OFFICE   => 'success',
            self::HOME     => 'info',
            self::FIELD    => 'Secondary',
            self::HOLIDAY  => 'danger',
            self::LEAVE    => 'primary',
            self::WEEKEND  => 'danger',
            self::NOT_WORK  => 'danger',
            self::OVERTIME   => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OFFICE   => 'fas-building',
            self::HOME     => 'fas-house-laptop',
            self::FIELD    => 'fas-person-walking-luggage',
            self::HOLIDAY  => 'fas-person-skiing',
            self::LEAVE    => 'fas-person-circle-minus',
            self::WEEKEND  => 'fas-person-circle-minus',
            self::NOT_WORK  => 'fas-person-circle-minus',
            self::OVERTIME   => 'fas-building',
        };
    }
}
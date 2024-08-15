<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum GenderEnum: string implements HasLabel, HasColor, HasIcon
{
    case FEMALE = 'female';
    case MALE   = 'male';    

    public static function fromString(string $value): self
    {
        return match($value) {
            'female'    => self::FEMALE,
            'male'      => self::MALE,
            'Female'    => self::FEMALE,
            'Male'      => self::MALE,
            'f'         => self::FEMALE,
            'm'         => self::MALE,
            'F'         => self::FEMALE,
            'M'         => self::MALE,
        };
    }

    public function getLabel(): ?string
    {        
        return match ($this) {
            self::FEMALE    => __('field.female'),
            self::MALE      => __('field.male')
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::FEMALE    => 'success',
            self::MALE      => 'info'
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FEMALE    => 'fas-person-dress',
            self::MALE      => 'fas-person'      
        };
    }
}

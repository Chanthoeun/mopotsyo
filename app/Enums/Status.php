<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Status: string implements HasLabel, HasColor, HasIcon
{
    case CREATED = 'created';
    case PENDING = 'pending';
    case WAITING = 'waiting';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case DISCARDED = 'discarded';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CREATED => __('msg.created'),
            self::PENDING => __('msg.pending'),
            self::WAITING => __('msg.waiting'),
            self::APPROVED => __('msg.approved'),
            self::REJECTED => __('msg.rejected'),
            self::DISCARDED => __('msg.discarded'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CREATED => 'gray',
            self::PENDING => 'warning',
            self::WAITING => 'gray',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::DISCARDED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CREATED => 'heroicon-o-pencil',
            self::PENDING => 'heroicon-o-clock',
            self::WAITING => 'heroicon-o-pause',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REJECTED => 'heroicon-o-x-circle',
            self::DISCARDED => 'heroicon-o-trash',
        };
    }
}

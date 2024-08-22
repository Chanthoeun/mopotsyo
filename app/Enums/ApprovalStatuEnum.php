<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ApprovalStatuEnum: int implements HasColor, HasIcon, HasLabel
{
    case SUBMITTED  = 0;
    case APPROVED   = 1;
    case REJECTED   = 2;
    case DISCARDED  = 3;
    case RETURNED   = 4;

    public static function fromString(string $value): self
    {
        return match($value) {
            __('field.approval.submitted')  => self::SUBMITTED,
            __('field.approval.approved')   => self::APPROVED,  
            __('field.approval.rejected')   => self::REJECTED,
            __('field.approval.discarded')  => self::DISCARDED,
            __('field.approval.returned')   => self::RETURNED,
        };
    }

    public function getLabel(): ?string
    {        
        return match ($this) {
            self::SUBMITTED     => __('field.approval.submitted'),
            self::APPROVED      => __('field.approval.approved'),
            self::REJECTED      => __('field.approval.rejected'),
            self::DISCARDED     => __('field.approval.discarded'),
            self::RETURNED      => __('field.approval.returned'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::SUBMITTED     => 'default',
            self::APPROVED      => 'success',
            self::REJECTED      => 'danger',
            self::DISCARDED     => 'primary',
            self::RETURNED      => 'info',            
        };
    }

    public function getIcon(): ?string
    {        
        return match ($this) {
            self::SUBMITTED     => 'fas-paper-plane',
            self::APPROVED      => 'fas-circle-check',
            self::REJECTED      => 'fas-circle-xmark',
            self::DISCARDED     => 'fas-circle-minus',
            self::RETURNED      => 'fas-rotate-left',
        };
    }
}

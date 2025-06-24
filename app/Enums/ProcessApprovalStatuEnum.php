<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ProcessApprovalStatuEnum: string implements HasColor, HasIcon, HasLabel
{
    case CREATED    = 'Created';
    case SUBMITTED  = 'Submitted';
    case PENDING    = 'Pending';
    case APPROVED   = 'Approved';
    case REJECTED   = 'Rejected';
    case DISCARDED  = 'Discarded';
    case RETURNED   = 'Returned';

    public static function fromString(string $value): self
    {
        return match($value) {
            __('field.approval.created')    => self::CREATED,
            __('field.approval.submitted')  => self::SUBMITTED,
            __('field.approval.pending')    => self::PENDING,
            __('field.approval.approved')   => self::APPROVED,  
            __('field.approval.rejected')   => self::REJECTED,
            __('field.approval.discarded')  => self::DISCARDED,
            __('field.approval.returned')   => self::RETURNED,
        };
    }
    public function getLabel(): ?string
    {        
        return match ($this) {
            self::CREATED       => __('field.approval.created'),
            self::SUBMITTED     => __('field.approval.submitted'),
            self::PENDING       => __('field.approval.pending'),
            self::APPROVED      => __('field.approval.approved'),
            self::REJECTED      => __('field.approval.rejected'),
            self::DISCARDED     => __('field.approval.discarded'),
            self::RETURNED      => __('field.approval.returned'),
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::CREATED       => 'secondary',
            self::SUBMITTED     => 'default',
            self::PENDING       => 'warning',   
            self::APPROVED      => 'success',
            self::REJECTED      => 'danger',
            self::DISCARDED     => 'primary',
            self::RETURNED      => 'info',         
        };
    }

    public function getIcon(): ?string
    {        
        return match ($this) {
            self::CREATED       => 'fas-hourglass-end',
            self::SUBMITTED     => 'fas-paper-plane',
            self::PENDING       => 'fas-hourglass-start',
            self::APPROVED      => 'fas-circle-check',
            self::REJECTED      => 'fas-circle-xmark',
            self::DISCARDED     => 'fas-circle-minus',
            self::RETURNED      => 'fas-rotate-left',
        };
    }
}

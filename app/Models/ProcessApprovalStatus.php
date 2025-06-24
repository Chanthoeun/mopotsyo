<?php

namespace App\Models;

use App\Enums\ProcessApprovalStatuEnum;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalStatus as BaseProcessApprovalStatus;

class ProcessApprovalStatus extends BaseProcessApprovalStatus
{
    
    protected $casts = [
        'steps' => 'array',
        'status' => ProcessApprovalStatuEnum::class
    ];
}

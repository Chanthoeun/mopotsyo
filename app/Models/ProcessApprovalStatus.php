<?php

namespace App\Models;

use App\Enums\ProcessApprovalStatuEnum;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalStatus as BaseProcessApprovalStatus;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

class ProcessApprovalStatus extends BaseProcessApprovalStatus
{
    
    protected $casts = [
        'steps' => 'array',
        'status' => ApprovalStatusEnum::class
    ];
}

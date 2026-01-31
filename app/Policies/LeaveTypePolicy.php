<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LeaveType;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\LeaveTypePolicy as BasePolicy;

class LeaveTypePolicy extends BasePolicy
{
}

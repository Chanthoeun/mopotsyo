<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\EmployeePolicy as BasePolicy;

class EmployeePolicy extends BasePolicy
{
}

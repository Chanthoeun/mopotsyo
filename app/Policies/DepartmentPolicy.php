<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\DepartmentPolicy as BasePolicy;

class DepartmentPolicy extends BasePolicy
{
}

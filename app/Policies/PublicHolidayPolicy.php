<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PublicHoliday;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\PublicHolidayPolicy as BasePolicy;

class PublicHolidayPolicy extends BasePolicy
{
}

<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\ActivityPolicy as BasePolicy;

class ActivityPolicy extends BasePolicy
{
}

<?php

namespace App\Policies;

use App\Models\User;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\AuthenticationLogPolicy as BasePolicy;

class AuthenticationLogPolicy extends BasePolicy
{
}

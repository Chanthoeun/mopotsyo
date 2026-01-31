<?php

namespace App\Policies;

use App\Models\User;
use RickDBCN\FilamentEmail\Models\Email;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\EmailPolicy as BasePolicy;

class EmailPolicy extends BasePolicy
{
}

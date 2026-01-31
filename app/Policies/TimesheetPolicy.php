<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Timesheet;
use Illuminate\Auth\Access\HandlesAuthorization;

use App\Policies\Base\TimesheetPolicy as BasePolicy;

class TimesheetPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Timesheet $timesheet): bool
    {
        if ($user->id == $timesheet->user_id)
            return true;

        return $user->can('view_timesheet');
    }
}

<?php

namespace App\Traits;

use App\Models\LeaveRequest;

trait LeaveRequestProcess
{
    public function leaveRequestSubmit(LeaveRequest $leaveRequest)
    {
        $this->leaveRequestProcess = true;
    }
}

<?php

namespace App\Traits;

use RingleSoft\LaravelProcessApproval\Enums\ApprovalActionEnum;

trait HasCustomApproval
{
    /**
     * Check if Approval process is completed based on active steps.
     * This overrides the default behavior from the Approvable trait.
     *
     * @param array|null $currentSteps
     * @return bool
     */
    public function isApprovalCompleted(?array $currentSteps = null): bool
    {
        $registeredSteps = $currentSteps ? collect($currentSteps) : collect($this->approvalStatus->steps ?? []);

        if ($registeredSteps->count() > 0) {
            // Filter out steps that are not part of the current flow for this request
            $activeSteps = $registeredSteps->where('active', true);

            if ($activeSteps->count() === 0) {
                // If there are no active steps, it means no approval is needed.
                return true;
            }

            return $activeSteps->every(fn($item) => $item['process_approval_action'] !== null && $item['process_approval_id'] !== null && $item['process_approval_action'] !== ApprovalActionEnum::RETURNED->value)
                && $activeSteps->last()['process_approval_action'] !== ApprovalActionEnum::REJECTED->value;
        }
        return true;
    }
}

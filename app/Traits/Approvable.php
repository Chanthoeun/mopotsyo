<?php

namespace App\Traits;

use App\Models\ApprovalStep;
use App\Models\Aprovers;
use App\Models\LeaveRequest;
use App\Models\OverTime;
use App\Models\PurchaseRequest;
use App\Models\SwitchWorkDay;
use App\Models\WorkFromHome;
use App\Settings\SettingOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;


trait Approvable
{
    public function approvalSteps(): MorphMany
    {
        return $this->morphMany(ApprovalStep::class, 'approvable')->orderBy('level');
    }

    public function currentApprovalStep()
    {
        if ($this->relationLoaded('approvalSteps')) {
            return $this->approvalSteps->where('status', \App\Enums\Status::PENDING)->first();
        }
        return $this->approvalSteps()->where('status', \App\Enums\Status::PENDING)->first();
    }

    protected function currentApproverName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $step = $this->currentApprovalStep();
                if (!$step) {
                    return '-';
                }

                if ($step->relationLoaded('approver') && $step->approver) {
                    return $step->approver->name;
                }

                return $step->approver?->name ?? '-';
            }
        );
    }

    public function isApproved(): bool
    {
        return $this->approvalSteps()->count() > 0 && $this->approvalSteps()->where('status', '!=', \App\Enums\Status::APPROVED)->count() === 0;
    }

    public function isRejected(): bool
    {
        return $this->status === \App\Enums\Status::REJECTED;
    }

    public function isSubmitted(): bool
    {
        return $this->status === \App\Enums\Status::PENDING && $this->approvalSteps()->count() > 0;
    }

    public function isDiscarded(): bool
    {
        return $this->status === \App\Enums\Status::DISCARDED;
    }

    public function isApprovalCompleted(): bool
    {
        return $this->status === \App\Enums\Status::APPROVED;
    }

    public function canBeApprovedBy($user): bool
    {
        $currentStep = $this->currentApprovalStep();
        return $currentStep && $currentStep->approver_id === $user->id;
    }

    public function nextApprovalStep()
    {
        return $this->currentApprovalStep();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', \App\Enums\Status::APPROVED);
    }

    public function submitToApproval()
    {
        $user = $this->user ?? Auth::user();
        if (!$user || !$user->contract) {
            return;
        }

        // Clear existing steps if any (optional, but good for re-submission)
        $this->approvalSteps()->delete();

        $roles = [];
        $modelClass = get_class($this);

        if ($this instanceof LeaveRequest) {
            $leaveType = $this->leaveType;
            if ($leaveType && !empty($leaveType->rules)) {
                foreach ($leaveType->rules as $rule) {
                    if ($this->days >= $rule['from_amount'] && (empty($rule['to_amount']) || $this->days <= $rule['to_amount'])) {
                        $roles = $rule['roles'];
                        break; // Use the first matching rule
                    }
                }
            }
        } elseif ($this instanceof WorkFromHome) {
            $rules = app(SettingOptions::class)->work_from_home_rules;
            if (!empty($rules)) {
                foreach ($rules as $rule) {
                    if ($this->days >= $rule['from_amount'] && (empty($rule['to_amount']) || $this->days <= $rule['to_amount'])) {
                        $roles = $rule['roles'];
                        break;
                    }
                }
            }
        }


        $stepsCreated = 0;

        // If no specific rules found roles, fetch all default approvers for this model from the contract
        if (empty($roles)) {
            $contractApprovers = $user->contract->approvers()->where('model_type', $modelClass)->get();

            // Fallback: If no approvers configured, assign to Supervisor
            if ($contractApprovers->isEmpty() && $user->contract->supervisor_id) {
                ApprovalStep::create([
                    'approvable_type' => $modelClass,
                    'approvable_id' => $this->id,
                    'approver_id' => $user->contract->supervisor_id,
                    'role_id' => null,
                    'level' => 1,
                    'status' => \App\Enums\Status::PENDING,
                ]);
                $stepsCreated++;
            }

            foreach ($contractApprovers as $index => $contractApprover) {
                // First step is PENDING, subsequent steps are WAITING
                $status = ($index == 0 && $stepsCreated == 0) ? \App\Enums\Status::PENDING : \App\Enums\Status::WAITING;

                ApprovalStep::create([
                    'approvable_type' => $modelClass,
                    'approvable_id' => $this->id,
                    'approver_id' => $contractApprover->approver_id,
                    'role_id' => $contractApprover->role_id,
                    'level' => $index + 1 + ($user->contract->supervisor_id && $contractApprovers->isEmpty() ? 1 : 0),
                    'status' => $status,
                ]);
                $stepsCreated++;
            }
        } else {
            // Rules defined roles, find specific approvers for these roles in the contract
            foreach ($roles as $index => $roleId) {
                $contractApprover = $user->contract->approvers()
                    ->where('model_type', $modelClass)
                    ->where('role_id', $roleId)
                    ->first();

                // First step is PENDING, subsequent steps are WAITING
                $status = ($index == 0) ? \App\Enums\Status::PENDING : \App\Enums\Status::WAITING;

                ApprovalStep::create([
                    'approvable_type' => $modelClass,
                    'approvable_id' => $this->id,
                    'approver_id' => $contractApprover?->approver_id, // Might be null if not configured
                    'role_id' => $roleId,
                    'level' => $index + 1,
                    'status' => $status,
                ]);
                $stepsCreated++;
            }
        }

        if ($stepsCreated === 0) {
            $this->update(['status' => \App\Enums\Status::APPROVED]);
            \App\Events\ApprovalProcessed::dispatch($this, 'approved', 'Auto-approved (No approvers configured)');
        } else {
            // Update status to PENDING
            $this->update(['status' => \App\Enums\Status::PENDING]);
            // Dispatch event for processing (notifies first approver)
            \App\Events\ApprovalProcessed::dispatch($this, 'submitted');
        }
    }

    public function getFilamentUrl(): string
    {
        $resource = match (get_class($this)) {
            LeaveRequest::class => \App\Filament\Admin\Resources\LeaveRequestResource::class,
            OverTime::class => \App\Filament\Admin\Resources\OverTimeResource::class,
            WorkFromHome::class => \App\Filament\Admin\Resources\WorkFromHomeResource::class,
            PurchaseRequest::class => \App\Filament\Admin\Resources\PurchaseRequestResource::class,
            SwitchWorkDay::class => \App\Filament\Admin\Resources\SwitchWorkDayResource::class,
            default => null,
        };

        if ($resource) {
            return $resource::getUrl('view', ['record' => $this->id]);
        }

        return '#';
    }
}

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
use Filament\Notifications\Notification;
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

    /**
     * Submit the record for approval processing.
     * 
     * @return bool True if submitted successfully or auto-approved, false if blocked by missing configuration.
     */
    public function submitToApproval(): bool
    {
        $user = $this->user ?? Auth::user();
        if (!$user || !$user->contract) {
            return false;
        }

        $modelClass = get_class($this);
        $requiredRoleIds = [];
        $isRuleBased = false;

        // 1. Identify required roles/paths
        if ($this instanceof LeaveRequest) {
            $leaveType = $this->leaveType;
            if ($leaveType && !empty($leaveType->rules)) {
                foreach ($leaveType->rules as $rule) {
                    if ($this->days >= $rule['from_amount'] && (empty($rule['to_amount']) || $this->days <= $rule['to_amount'])) {
                        $requiredRoleIds = $rule['roles'] ?? [];
                        $isRuleBased = true;
                        break;
                    }
                }
            }
        } elseif ($this instanceof WorkFromHome) {
            $rules = app(SettingOptions::class)->work_from_home_rules;
            if (!empty($rules)) {
                foreach ($rules as $rule) {
                    if ($this->days >= $rule['from_amount'] && (empty($rule['to_amount']) || $this->days <= $rule['to_amount'])) {
                        $requiredRoleIds = $rule['roles'] ?? [];
                        $isRuleBased = true;
                        break;
                    }
                }
            }
        }

        // 2. Clear existing steps and start creation
        $this->approvalSteps()->delete();
        $stepsCreated = 0;

        // 3. Fetch Contract Approvers in Order (Source of Truth)
        $contractApprovers = $user->contract->approvers()
            ->where('model_type', $modelClass)
            ->whereNotNull('approver_id')
            ->orderBy('sort')
            ->get();

        // 4. Workflow Construction
        $finalSteps = collect();

        // 4a. Handle Direct Supervisor Fallback (Virtual Step)
        static $supervisorRoleIdCache = null;
        if ($supervisorRoleIdCache === null) {
            $supervisorRoleIdCache = \Spatie\Permission\Models\Role::where('name', 'supervisor')->first()?->id;
        }
        $supervisorRoleId = $supervisorRoleIdCache;
        $directSupervisorId = $user->contract->supervisor_id;

        $supervisorIsRequired = $isRuleBased
            ? in_array($supervisorRoleId, $requiredRoleIds)
            : ($contractApprovers->isEmpty() && $directSupervisorId);

        $supervisorExplicitlyConfigured = $contractApprovers->contains('role_id', $supervisorRoleId);

        if ($supervisorIsRequired && !$supervisorExplicitlyConfigured && $directSupervisorId) {
            $finalSteps->push([
                'approver_id' => $directSupervisorId,
                'role_id' => $supervisorRoleId,
                'is_virtual' => true
            ]);
        }

        // 4b. Handle Department Head Fallback (Virtual Step)
        static $hodRoleIdCache = null;
        if ($hodRoleIdCache === null) {
            $hodRoleIdCache = \Spatie\Permission\Models\Role::where('name', 'head_of_department')->first()?->id;
        }
        $hodRoleId = $hodRoleIdCache;
        $directHodId = $user->contract->department_head_id;

        $hodIsRequired = $isRuleBased && in_array($hodRoleId, $requiredRoleIds);
        $hodExplicitlyConfigured = $contractApprovers->contains('role_id', $hodRoleId);

        // Only add Virtual HoD if required, not configured, AND distinct from the Virtual Supervisor we just added (if any)
        if ($hodIsRequired && !$hodExplicitlyConfigured && $directHodId) {
            // Avoid adding if same as supervisor fallback to prevent immediate partial duplication
            if (!($supervisorIsRequired && !$supervisorExplicitlyConfigured && $directSupervisorId === $directHodId)) {
                $finalSteps->push([
                    'approver_id' => $directHodId,
                    'role_id' => $hodRoleId,
                    'is_virtual' => true
                ]);
            }
        }

        // 4c. Add Configured Approvers
        foreach ($contractApprovers as $approver) {
            if ($isRuleBased && !in_array($approver->role_id, $requiredRoleIds)) {
                continue;
            }
            $finalSteps->push([
                'approver_id' => $approver->approver_id,
                'role_id' => $approver->role_id,
                'is_virtual' => false
            ]);
        }

        // 5. Validation: Did we satisfy all *Required* Roles?
        if ($isRuleBased) {
            $coveredRoleIds = $finalSteps->pluck('role_id')->toArray();

            // If Supervisor/HoD were handled by fallback (even if merged), consider them covered
            if ($supervisorIsRequired && !$supervisorExplicitlyConfigured && $directSupervisorId)
                $coveredRoleIds[] = $supervisorRoleId;
            if ($hodIsRequired && !$hodExplicitlyConfigured && $directHodId)
                $coveredRoleIds[] = $hodRoleId;

            $missingRoles = array_diff($requiredRoleIds, $coveredRoleIds);

            if (!empty($missingRoles)) {
                $missingRoleNames = \Spatie\Permission\Models\Role::whereIn('id', $missingRoles)->pluck('name')->toArray();
                Notification::make()
                    ->title(__('msg.label.error'))
                    ->body(__('msg.body.missing_approver_role', ['roles' => implode(', ', $missingRoleNames)]))
                    ->danger()
                    ->persistent()
                    ->send();
                return false;
            }
        }

        // 6. Deduplicate Consecutive Approvers
        // If the same user appears in consecutive steps, we merge them (effectively skipping the second approval).
        // This handles cases where Supervisor == Department Head.
        $deduplicatedSteps = collect();
        $lastApproverId = null;

        foreach ($finalSteps as $step) {
            if ($step['approver_id'] !== $lastApproverId) {
                $deduplicatedSteps->push($step);
                $lastApproverId = $step['approver_id'];
            }
        }

        // 7. Persist Steps
        if ($deduplicatedSteps->isEmpty()) {
            if ($isRuleBased && !empty($requiredRoleIds)) {
                return false;
            }

            $this->update(['status' => \App\Enums\Status::APPROVED]);
            \App\Events\ApprovalProcessed::dispatch($this, 'approved', 'Auto-approved (No approvers configured)', $user);
            return true;
        }

        foreach ($deduplicatedSteps as $index => $stepData) {
            $status = ($index == 0) ? \App\Enums\Status::PENDING : \App\Enums\Status::WAITING;

            ApprovalStep::create([
                'approvable_type' => $modelClass,
                'approvable_id' => $this->id,
                'approver_id' => $stepData['approver_id'],
                'role_id' => $stepData['role_id'],
                'level' => $index + 1,
                'status' => $status,
            ]);
            $stepsCreated++;
        }

        $this->update(['status' => \App\Enums\Status::PENDING]);
        \App\Events\ApprovalProcessed::dispatch($this, 'submitted', null, $user);

        return true;
    }

    public function validateContractConfiguration($user = null): bool
    {
        $user = $user ?? $this->user ?? Auth::user();
        if (!$user || !$user->contract) {
            Notification::make()
                ->title(__('msg.label.error'))
                ->body('User has no valid contract.')
                ->danger()
                ->send();
            return false;
        }

        $hasSupervisor = (bool) $user->contract->supervisor_id;
        $hasApprovers = $user->contract->approvers()
            ->where('model_type', get_class($this))
            ->whereNotNull('approver_id')
            ->exists();

        if (!$hasSupervisor && !$hasApprovers) {
            Notification::make()
                ->title(__('msg.label.error'))
                ->body(__('msg.body.no_approver_configured'))
                ->danger()
                ->persistent()
                ->send();
            return false;
        }

        return true;
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

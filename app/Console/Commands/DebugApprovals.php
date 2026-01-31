<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Console\Command;

class DebugApprovals extends Command
{
    protected $signature = 'approvals:debug {email}';
    protected $description = 'Debug approval access for a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return;
        }

        $this->info("Debugging access for User: {$user->name} (ID: {$user->id})");
        $this->info("Direct Permissions: " . json_encode($user->getPermissionNames()));
        $this->info("All Permissions: " . json_encode($user->getAllPermissions()->pluck('name')));

        $this->info("\nChecking Pending Leave Requests assigned to this user...");

        $pendingRequests = LeaveRequest::where('status', 'pending')->get();
        $assignedCount = 0;

        foreach ($pendingRequests as $request) {
            $currentStep = $request->currentApprovalStep();

            if ($currentStep && $currentStep->approver_id == $user->id) {
                $assignedCount++;
                $this->line("- Request ID: {$request->id} (Requester: {$request->user->name}, From: {$request->from_date->toDateString()})");
                $this->line("  Step Level: {$currentStep->level}, Status: {$currentStep->status->value}");

                // Check Policy
                $canApprove = $user->can('approve', $request);
                $this->line("  Policy 'approve' check: " . ($canApprove ? 'TRUE' : 'FALSE'));
            }
        }

        $this->info("\nTotal pending requests where this user is the current approver: {$assignedCount}");

        if ($assignedCount === 0) {
            $this->warn("This user is not currently the active approver for any pending leave requests.");

            $this->info("\nChecking if user is in any FUTURE steps for pending requests...");
            $futureCount = 0;
            foreach ($pendingRequests as $request) {
                $myStep = $request->approvalSteps()->where('approver_id', $user->id)->first();
                if ($myStep && $myStep->status->value === 'waiting') {
                    $futureCount++;
                    $currentApprover = User::find($request->currentApprovalStep()?->approver_id);
                    $this->line("- Request ID: {$request->id} (Requester: {$request->user->name})");
                    $this->line("  You are Level {$myStep->level}. Currently awaiting Level " . ($request->currentApprovalStep()?->level ?? '?') . " (" . ($currentApprover?->name ?? 'Unknown') . ")");
                }
            }
            $this->info("Total requests awaiting this user in future steps: {$futureCount}");
        }
    }
}

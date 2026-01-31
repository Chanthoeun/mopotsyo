<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Enums\Status;

class FixMissingApprovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:fix-missing-approvers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix pending approval steps that have a missing approver_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Identifying pending approval steps with missing approvers...");

        // Find all pending approval steps with NULL approver_id
        $badSteps = ApprovalStep::where('status', Status::PENDING)
            ->whereNull('approver_id')
            ->get();

        $count = $badSteps->count();
        $this->info("Found {$count} potential bad steps.");

        if ($count === 0) {
            $this->info("No steps to fix.");
            return;
        }

        if (!$this->option('no-interaction') && !$this->confirm("Do you want to attempt to fix these {$count} steps?")) {
            return;
        }

        $fixedCount = 0;
        $skippedCount = 0;

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($badSteps as $step) {
            // Get the request model
            $modelClass = $step->approvable_type;
            $modelId = $step->approvable_id;

            // Check if model exists
            if (!class_exists($modelClass)) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            $request = $modelClass::find($modelId);

            if (!$request) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            // Get the requester (User)
            $requester = $request->user;
            if (!$requester || !$requester->employee) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            // Get active contract
            $contract = $requester->employee->contracts()->where('is_active', true)->first();

            if (!$contract) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            // Check roles and assign correct approver
            $roleName = $step->role ? $step->role->name : 'unknown';
            $approverId = null;

            if ($roleName === 'supervisor') {
                $approverId = $contract->supervisor_id;
            } elseif ($roleName === 'head_of_department') {
                $approverId = $contract->department_head_id;
            } elseif ($roleName === 'acting_director') {
                $approverId = 2; // Hardcoded ID 2 for acting director
            }

            if ($approverId) {
                $step->approver_id = $approverId;
                $step->save();
                $fixedCount++;
            } else {
                $skippedCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Process completed.");
        $this->info("Fixed: {$fixedCount}");
        $this->info("Skipped: {$skippedCount}");
    }
}

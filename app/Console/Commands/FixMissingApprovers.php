<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalStep;
use App\Enums\Status;

class FixMissingApprovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:fix-broken-requests {--force : Force repair without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and repair broken approval workflows (e.g. pending requests with no approver). Resets workflow for affected records.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = [
            \App\Models\LeaveRequest::class,
            \App\Models\OverTime::class,
            \App\Models\WorkFromHome::class,
            \App\Models\PurchaseRequest::class,
            \App\Models\SwitchWorkDay::class,
        ];

        $totalFixed = 0;

        foreach ($models as $modelClass) {
            $this->info("Checking " . class_basename($modelClass) . "...");

            // Find Pending records
            $pendingRecords = $modelClass::where('status', Status::PENDING)->get();
            $brokenRecords = $pendingRecords->filter(function ($record) {
                $step = $record->currentApprovalStep();

                // Case 1: Record is PENDING but has no current PENDING step. (Stuck/Inconsistent)
                if (!$step) {
                    return true;
                }

                // Case 2: Current step has no approver assigned.
                if (empty($step->approver_id)) {
                    return true;
                }

                return false;
            });

            $count = $brokenRecords->count();
            if ($count === 0) {
                // $this->info("No broken records found."); // Verbose
                continue;
            }

            $this->warn("Found {$count} broken records for " . class_basename($modelClass));

            if (!$this->option('force') && !$this->confirm("Do you want to reset and regenerate approval steps for these {$count} records?")) {
                continue;
            }

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($brokenRecords as $record) {
                try {
                    // submitToApproval() will clear existing steps and regenerate them using current rules.
                    if ($record->submitToApproval()) {
                        $totalFixed++;
                    } else {
                        // Could not submit (e.g. still no approver config).
                        // Consider logging error?
                    }
                } catch (\Exception $e) {
                    // $this->error("Failed to fix {$record->id}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info("Process completed. Total records repaired: {$totalFixed}");
    }
}

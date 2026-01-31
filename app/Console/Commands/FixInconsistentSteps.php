<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Models\OverTime;
use App\Models\PurchaseRequest;
use App\Models\SwitchWorkDay;
use App\Models\WorkFromHome;
use App\Enums\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixInconsistentSteps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:fix-steps {--dry-run : Only report inconsistencies without fixing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix inconsistent approval step statuses (e.g., multiple pending steps)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $models = [
            LeaveRequest::class,
            OverTime::class,
            SwitchWorkDay::class,
            WorkFromHome::class,
            PurchaseRequest::class,
        ];

        foreach ($models as $modelClass) {
            $this->info("Checking $modelClass...");
            $this->fixInconsistentStepsForModel($modelClass);
        }

        $this->info('Done!');
    }

    protected function fixInconsistentStepsForModel($modelClass)
    {
        $records = $modelClass::with('approvalSteps')->get();

        foreach ($records as $record) {
            $steps = $record->approvalSteps->sortBy('level');

            if ($steps->isEmpty()) {
                continue;
            }



            if ($record->status === Status::APPROVED) {
                // All steps should be approved
                foreach ($steps as $step) {
                    if ($step->status !== Status::APPROVED) {
                        $this->warn("Record #{$record->id} is APPROVED but Step L{$step->level} is {$step->status->value}. Fixing...");
                        if (!$this->option('dry-run')) {
                            $step->update(['status' => Status::APPROVED]);
                        }
                    }
                }
            } elseif ($record->status === Status::PENDING) {
                // Find the first step that is not approved
                $foundPending = false;
                $allApproved = true;

                foreach ($steps as $index => $step) {
                    if ($step->status === Status::APPROVED) {
                        continue;
                    }

                    $allApproved = false;

                    if (!$foundPending) {
                        // This should be the only pending step
                        if ($step->status !== Status::PENDING) {
                            $this->warn("Record #{$record->id} is PENDING. Step L{$step->level} (First non-approved) is {$step->status->value}. Setting to PENDING...");
                            if (!$this->option('dry-run')) {
                                $step->update(['status' => Status::PENDING]);
                            }
                        }
                        $foundPending = true;
                    } else {
                        // All subsequent non-approved steps should be WAITING
                        if ($step->status !== Status::WAITING) {
                            $this->warn("Record #{$record->id} is PENDING. Step L{$step->level} (Subsequent) is {$step->status->value}. Setting to WAITING...");
                            if (!$this->option('dry-run')) {
                                $step->update(['status' => Status::WAITING]);
                            }
                        }
                    }
                }

                if ($allApproved) {
                    $this->warn("Record #{$record->id} is PENDING but ALL steps are APPROVED. Setting Record to APPROVED...");
                    if (!$this->option('dry-run')) {
                        $record->update(['status' => Status::APPROVED]);
                    }
                }
            }
        }
    }
}

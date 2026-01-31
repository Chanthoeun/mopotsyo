<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use App\Events\ApprovalProcessed;

class MigratePendingApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:migrate-pending 
                            {--dry-run : Only show what would be done} 
                            {--silent : Do not dispatch events/notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing pending records to the new approval system';

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

        $dryRun = $this->option('dry-run');
        $silent = $this->option('silent');

        if ($silent) {
            Event::fake([ApprovalProcessed::class]);
            $this->info("Silent mode enabled: Notifications will not be sent.");
        }

        foreach ($models as $modelClass) {
            $this->info("Processing $modelClass...");

            $pendingRecords = $modelClass::where('status', \App\Enums\Status::PENDING)
                ->whereDoesntHave('approvalSteps')
                ->get();

            $count = $pendingRecords->count();
            $this->info("Found $count pending records without approval steps.");

            if ($count === 0) {
                continue;
            }

            if ($dryRun) {
                $this->comment("Dry run: Would migrate $count records for " . class_basename($modelClass));
                continue;
            }

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($pendingRecords as $record) {
                try {
                    // This will generate approval steps and dispatch the submitted event
                    $record->submitToApproval();
                } catch (\Exception $e) {
                    $this->error("\nError migrating record ID {$record->id}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Migration completed for " . class_basename($modelClass));
        }

        $this->info('Migration process finished.');
    }
}

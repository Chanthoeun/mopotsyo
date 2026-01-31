<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpgradeApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:upgrade-approvals {--force : Force executions without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform the complete approval system upgrade (Migrate + Fix Steps + Restore Approvers)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Approval System Upgrade...');

        if (!$this->option('force') && !$this->confirm('This will run migrations and data reconciliation. Ensure you have a backup. Continue?')) {
            $this->warn('Upgrade cancelled.');
            return Command::FAILURE;
        }

        // 1. Run Migrations
        $this->info('Step 1: Running migrations and legacy data transfer...');
        Artisan::call('migrate', ['--force' => true], $this->output);

        // 2. Fix Steps
        $this->newLine();
        $this->info('Step 2: Reconciling step statuses...');
        Artisan::call('approvals:fix-steps', [], $this->output);

        // 3. Fix Missing Approvers
        $this->newLine();
        $this->info('Step 3: Restoring missing approvers from contract history...');
        // We use system call or manual interaction handling if needed, 
        // and fix-broken-requests uses $this->confirm inside.
        // Let's call it with no-interaction if force is on.
        Artisan::call('approvals:fix-broken-requests', [
            '--force' => true,
            '--no-interaction' => true,
        ], $this->output);

        // 4. Migrate Pending
        $this->newLine();
        $this->info('Step 4: Migrating new pending records...');
        Artisan::call('approvals:migrate-pending', ['--silent' => true], $this->output);

        $this->newLine();
        $this->info('Step 5: Clearing cache...');
        Artisan::call('optimize:clear', [], $this->output);

        $this->newLine();
        $this->info('Congratulations! Approval system upgrade is complete.');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to reset leave request, overtime, switch work day, work from home, purchae request, quote evaluation, and purchase order data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('request_dates')->truncate();
        DB::table('request_items')->truncate();
        DB::table('process_approvers')->truncate();
        DB::table('process_approvals')->truncate();
        DB::table('process_approval_statuses')->truncate();
        DB::table('leave_requests')->truncate();
        DB::table('over_times')->truncate();
        DB::table('leave_request_over_time')->truncate();
        DB::table('switch_work_days')->truncate();
        DB::table('work_from_homes')->truncate();
        DB::table('purchase_requests')->truncate();
        DB::table('timesheet_dates')->truncate();
        DB::table('timesheets')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Job is done!');
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RestoreLegacyStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approvals:restore-legacy {file? : Path to the SQL dump file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore legacy approval statuses from SQL dump file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?? public_path('data/mopotsyo-mas-backup.sql');

        if (!File::exists($filePath)) {
            $this->error("Backup file not found at: $filePath");
            return;
        }

        $this->info("Reading backup file: $filePath");

        // Read the entire file purely as string (it's small now, assumed filtered)
        // If it's the full backup, this will fail or be slow, but we trust the grep step.
        $content = file_get_contents($filePath);

        // Find the INSERT statement
        $prefix = "INSERT INTO `process_approval_statuses`";
        $pos = strpos($content, $prefix);

        if ($pos === false) {
            $this->error("No relevant INSERT statement found in file.");
            return;
        }

        $this->info("Parsing records...");

        // Extract the values part
        // Assuming content is like: ... INSERT INTO `process_approval_statuses` VALUES (....);
        // We find the first '(' after VALUES
        $valuesPos = strpos($content, 'VALUES', $pos);
        if ($valuesPos === false) {
            $this->error("No VALUES statement found.");
            return;
        }

        $start = strpos($content, '(', $valuesPos);
        // Find the end defined by ');' or just end of file if grep extracted well
        $end = strrpos($content, ');');
        if ($end === false) {
            $end = strlen($content);
        }

        // Substring containing all tuples: (1, '...', ...), (2, '...', ...)
        $data = substr($content, $start + 1, $end - $start - 2); // approximate trimming

        // Split by '),(' which is the standard separator for mysqldump extended inserts
        $records = explode('),(', $data);

        $bar = $this->output->createProgressBar(count($records));
        $bar->start();
        $restoredCount = 0;

        DB::transaction(function () use ($records, $bar, &$restoredCount) {
            foreach ($records as $record) {
                // $record is like: 782,'App\\Models\\LeaveRequest',749,'[...]','Approved',...
                // Or for first/last items it might have extra chars if trim wasn't perfect, but explode handles most.
                // Clean up potentially leading '(' or trailing ')' just in case
                $record = trim($record, "(); \t\n\r\0\x0B");

                // Extract Type and ID (first 3 parts)
                // 782,'App\Models\LeaveRequest',749,...
                $parts = explode(',', $record, 4);
                if (count($parts) < 4)
                    continue;

                $modelTypeRaw = $parts[1]; // 'App\\Models\\LeaveRequest'
                $modelIdRaw = $parts[2];   // 749

                $modelType = trim(str_replace(['\'', '\\\\'], ['', '\\'], $modelTypeRaw));
                $modelId = trim($modelIdRaw);

                // Extract Status
                // Status is the 5th column usually?
                // (id, type, app_id, steps, status, ...)
                // Steps is a JSON blob that might contain commas.
                // BUT Status is a string literal: 'Approved'.
                // Steps ends with ']' usually?
                // Safer: Regex for the status pattern in the string

                $status = Status::PENDING;
                if (str_contains($record, "'Approved'"))
                    $status = Status::APPROVED;
                elseif (str_contains($record, "'Rejected'"))
                    $status = Status::REJECTED;
                elseif (str_contains($record, "'Discarded'"))
                    $status = Status::DISCARDED;

                // Note: JSON might contain "Approved" (double quotes). We look for 'Approved' (single quotes).
                // Mysqldump wraps strings in single quotes.

                if ($status !== Status::PENDING) {
                    $this->updateModelStatus($modelType, $modelId, $status);
                    $restoredCount++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Restored status for $restoredCount records.");
    }

    protected function updateModelStatus($modelClass, $modelId, Status $status)
    {
        try {
            if (class_exists($modelClass)) {
                $table = (new $modelClass)->getTable();
                DB::table($table)
                    ->where('id', $modelId)
                    ->update(['status' => $status->value]);
            }
        } catch (\Exception $e) {
            // Ignore missing tables/models
        }
    }
}

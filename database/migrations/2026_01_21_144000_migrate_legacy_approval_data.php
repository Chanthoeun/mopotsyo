<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\Status;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('process_approval_statuses') || !Schema::hasTable('approval_steps')) {
            return;
        }

        $statuses = DB::table('process_approval_statuses')->get();

        foreach ($statuses as $statusRecord) {
            $steps = json_decode($statusRecord->steps, true);

            if (!is_array($steps)) {
                continue;
            }

            foreach ($steps as $index => $stepData) {
                $level = $index + 1;
                $approvalStepId = $stepData['process_approval_id'] ?? null;

                $decidedAt = null;
                $comment = null;
                $approverId = null;
                $status = Status::WAITING;

                if ($approvalStepId) {
                    $approvalRecord = DB::table('process_approvals')->where('id', $approvalStepId)->first();
                    if ($approvalRecord) {
                        $decidedAt = $approvalRecord->created_at;
                        $comment = $approvalRecord->comment;
                        $approverId = $approvalRecord->user_id;

                        $action = strtolower($approvalRecord->approval_action);
                        if (str_contains($action, 'approve')) {
                            $status = Status::APPROVED;
                        } elseif (str_contains($action, 'reject')) {
                            $status = Status::REJECTED;
                        } elseif (str_contains($action, 'discard')) {
                            $status = Status::DISCARDED;
                        } else {
                            $status = Status::PENDING;
                        }
                    }
                } else {
                    // Determine if PENDING or WAITING
                    // If it's the first step and no approval yet, it might be PENDING
                    if ($index === 0) {
                        $status = Status::PENDING;
                    } else {
                        // Check if previous step was approved
                        // (Simplified: we'll follow up with fix-steps command anyway)
                        $status = Status::WAITING;
                    }
                }

                DB::table('approval_steps')->insert([
                    'approvable_type' => $statusRecord->approvable_type,
                    'approvable_id' => $statusRecord->approvable_id,
                    'approver_id' => $approverId,
                    'role_id' => $stepData['role_id'] ?? null,
                    'level' => $level,
                    'status' => $status->value,
                    'comment' => $comment,
                    'decided_at' => $decidedAt,
                    'created_at' => $statusRecord->created_at,
                    'updated_at' => $statusRecord->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible action for data migration usually, 
        // especially since legacy tables will be dropped in later migration.
    }
};

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

        // Cache roles for fast lookup
        $supervisorRole = DB::table('roles')->where('name', 'supervisor')->first();
        $hodRole = DB::table('roles')->where('name', 'head_of_department')->first();

        $statuses = DB::table('process_approval_statuses')->get();

        foreach ($statuses as $statusRecord) {
            $steps = json_decode($statusRecord->steps, true);

            if (!is_array($steps)) {
                continue;
            }

            // Find the owner of the request to look up their contract
            $table = (new $statusRecord->approvable_type)->getTable();
            $request = DB::table($table)->where('id', $statusRecord->approvable_id)->first();

            if (!$request || !isset($request->user_id)) {
                continue;
            }

            // Get employee record
            $employee = DB::table('employees')->where('user_id', $request->user_id)->first();
            if (!$employee) {
                continue;
            }

            // Get active contract and its approvers
            $contract = DB::table('employee_contracts')
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->first();

            $contractApprovers = $contract
                ? DB::table('aprovers')
                    ->where('contract_id', $contract->id)
                    ->where('model_type', $statusRecord->approvable_type)
                    ->get()
                    ->pluck('approver_id', 'role_id')
                : collect();

            foreach ($steps as $index => $stepData) {
                $level = $index + 1;
                $approvalStepId = $stepData['process_approval_id'] ?? null;
                $roleId = $stepData['role_id'] ?? null;

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
                    // Resolve approver_id for pending/waiting steps from contract
                    if ($roleId) {
                        $approverId = $contractApprovers->get($roleId);

                        // Handle fallbacks if not explicitly configured in contract_approvers
                        if (!$approverId && $contract) {
                            if ($supervisorRole && $roleId == $supervisorRole->id) {
                                $approverId = $contract->supervisor_id;
                            } elseif ($hodRole && $roleId == $hodRole->id) {
                                $approverId = $contract->department_head_id;
                            }
                        }
                    }

                    // Determine if PENDING or WAITING
                    if ($index === 0) {
                        $status = Status::PENDING;
                    } else {
                        $status = Status::WAITING;
                    }
                }

                // Check if already exists (idempotency)
                $exists = DB::table('approval_steps')
                    ->where('approvable_type', $statusRecord->approvable_type)
                    ->where('approvable_id', $statusRecord->approvable_id)
                    ->where('level', $level)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('approval_steps')->insert([
                    'approvable_type' => $statusRecord->approvable_type,
                    'approvable_id' => $statusRecord->approvable_id,
                    'approver_id' => $approverId,
                    'role_id' => $roleId,
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

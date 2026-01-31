<?php

namespace App\Console\Commands;

use App\Models\LeaveCarryForward;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Settings\SettingOptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGenerateEntitlement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-generate-entitlement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto generate entitlement and carry forward when active entitlement expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired entitlements...');

        // Find active entitlements that have expired (end_date < today)
        $expiredEntitlements = LeaveEntitlement::where('is_active', true)
            ->whereDate('end_date', '<', now())
            ->get();

        if ($expiredEntitlements->isEmpty()) {
            $this->info('No expired entitlements found.');
            return;
        }

        $bar = $this->output->createProgressBar($expiredEntitlements->count());
        $bar->start();

        foreach ($expiredEntitlements as $entitlement) {
            DB::transaction(function () use ($entitlement) {
                // 1. Calculate and Create Carry Forward
                $this->processCarryForward($entitlement);

                // 2. Create New Entitlement
                $this->processNewEntitlement($entitlement);

                // 3. Deactivate Old Entitlement
                $entitlement->update(['is_active' => false]);
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Entitlement generation completed successfully.');
    }

    protected function processCarryForward(LeaveEntitlement $entitlement)
    {
        $leaveType = $entitlement->leaveType;

        // Check if Carry Forward is allowed
        // Usually checked via option ['allow_carry_forward' => true]
        $allowCarryForward = false;
        if (isset($leaveType->option['allow_carry_forward']) && $leaveType->option['allow_carry_forward']) {
            $allowCarryForward = true;
        }

        if (!$allowCarryForward) {
            return;
        }

        // Calculate actual remaining
        // Note: $entitlement->remaining attribute might effectively calculate this, 
        // but we want to be sure we capture the snapshot correctly at expiration.
        // The 'remaining' accessor subtracts taken and *linked* CF. Here we want pure remaining from the entitlement itself.
        // Actually, entitlement->remaining logic is: balance - allTaken - carryForwardBalance.
        // Since no CF is linked yet for *this* period (we are creating it for the *next*), 
        // we can use: balance - allTaken.

        $balance = $entitlement->balance;
        $taken = $entitlement->all_taken; // Uses helper getTakenLeave internally
        $remaining = max(0, $balance - $taken);

        if ($remaining > 0) {
            // Check max limits
            $maxCarryForward = $leaveType->option['max_carry_forward'] ?? 999;
            $carryAmount = min($remaining, $maxCarryForward);

            // Determine Expiry
            // Default to 6 months if not set
            $expiryMonths = $leaveType->option['carry_forward_expiry_months'] ?? 6;
            $startDate = \Carbon\Carbon::parse($entitlement->end_date)->addDay();
            $endDate = $startDate->copy()->addMonths($expiryMonths)->subDay();

            LeaveCarryForward::create([
                'user_id' => $entitlement->user_id,
                'leave_entitlement_id' => $entitlement->id, // Link to the OLD entitlement as source
                'balance' => $carryAmount,
                'taken' => 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => \App\Enums\Status::APPROVED, // Auto-approved
            ]);

            Log::info("Generated Carry Forward for User {$entitlement->user_id}: {$carryAmount} days.");
        }
    }

    protected function processNewEntitlement(LeaveEntitlement $entitlement)
    {
        $leaveType = $entitlement->leaveType;
        $user = $entitlement->user;

        $startDate = \Carbon\Carbon::parse($entitlement->end_date)->addDay();
        $endDate = $startDate->copy()->addYear()->subDay();

        $balance = $this->calculateNewBalance($leaveType, $user);

        LeaveEntitlement::create([
            'user_id' => $entitlement->user_id,
            'leave_type_id' => $entitlement->leave_type_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'balance' => $balance,
            'taken' => 0,
            'is_active' => true,
        ]);

        Log::info("Generated New Entitlement for User {$entitlement->user_id}: {$balance} days.");
    }

    protected function calculateNewBalance(LeaveType $leaveType, $user)
    {
        // Default to the Leave Type's standard balance
        // TODO: Implement seniority-based rules if required (e.g., +1 day per year of service)
        // Currently, 'rules' column is used for Approval Routing, not balance calculation.

        return $leaveType->balance;
    }
}

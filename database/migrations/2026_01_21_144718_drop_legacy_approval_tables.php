<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('process_approvals');
        Schema::dropIfExists('process_approval_flow_steps');
        Schema::dropIfExists('process_approval_flows');
        Schema::dropIfExists('process_approval_statuses');
        Schema::dropIfExists('process_approvers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

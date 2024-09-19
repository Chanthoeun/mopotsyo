<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_request_over_time', function (Blueprint $table) {
            $table->foreignId('leave_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('over_time_id')->constrained()->onDelete('cascade');
            $table->primary(['leave_request_id', 'over_time_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('leave_request_over_time');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};

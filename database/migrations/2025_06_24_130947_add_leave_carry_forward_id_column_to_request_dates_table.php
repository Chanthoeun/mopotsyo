<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_dates', function (Blueprint $table) {
            $table->foreignId('leave_carry_forward_id')->nullable()->after('requestdateable_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_carry_forward_id');
        });
    }
};

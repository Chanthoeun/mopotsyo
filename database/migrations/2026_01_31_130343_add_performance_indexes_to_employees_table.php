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
        Schema::table('employees', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('join_date');
            $table->index('resign_date');
            $table->index('created_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['join_date']);
            $table->dropIndex(['resign_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['deleted_at']);
        });
    }
};

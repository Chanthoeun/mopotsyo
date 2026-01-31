<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('switch_work_days', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('switch_work_days', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

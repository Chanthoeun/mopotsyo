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
        Schema::disableForeignKeyConstraints();

        Schema::create('employee_work_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('contract_id')->constrained('employee_contracts')->onDelete('restrict')->cascadeOnUpdate();
            $table->unsignedTinyInteger('day_name')->default(0);
            $table->time('start_time');
            $table->time('end_time');
            $table->float('break_time')->nullable();
            $table->time('break_from')->nullable();
            $table->time('break_to')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_work_days');
    }
};

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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('abbr', 5)->unique();
            $table->string('color', 7)->nullable();
            $table->boolean('male')->default(0);
            $table->boolean('female')->default(0);
            $table->unsignedTinyInteger('balance')->default(0);
            $table->unsignedTinyInteger('minimum_request_days')->default(0)->nullable();
            $table->string('balance_increment_period', 10)->nullable();
            $table->unsignedTinyInteger('balance_increment_amount')->default(0)->nullable();
            $table->unsignedTinyInteger('maximum_balance')->default(0)->nullable();
            $table->boolean('allow_carry_forward')->default(0);
            $table->string('carry_forward_duration', 10)->nullable();
            $table->boolean('allow_advance')->default(0);
            $table->unsignedTinyInteger('advance_limit')->default(0)->nullable();
            $table->boolean('allow_accrual')->default(0);
            $table->boolean('visible')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};

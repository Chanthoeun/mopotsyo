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

        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('contract_type_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->string('position');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignId('department_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('department_head_id')->nullable()->constrained('users')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('shift_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->string('contract_no')->nullable();
            $table->string('file')->nullable();
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
        Schema::dropIfExists('employee_contracts');
    }
};

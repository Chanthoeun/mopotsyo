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

        Schema::create('aprovers', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->foreignId('role_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('approver_id')->constrained('users')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('contract_id')->constrained('employee_contracts')->onDelete('restrict')->cascadeOnUpdate();
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
        Schema::dropIfExists('aprovers');
    }
};

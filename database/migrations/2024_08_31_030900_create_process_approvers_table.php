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

        Schema::create('process_approvers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('step_id');
            $table->foreignId('leave_request_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->unique(['step_id', 'leave_request_id']);
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
        Schema::dropIfExists('process_approvers');
    }
};

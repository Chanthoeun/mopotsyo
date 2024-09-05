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
            $table->string('modelable_type');
            $table->unsignedBigInteger('modelable_id');            
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('restrict')->cascadeOnUpdate();
            $table->unique(['step_id', 'modelable_type', 'modelable_id'], 'step_id_modelable_type_modelable_id_unique');
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

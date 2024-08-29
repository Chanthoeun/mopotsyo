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

        Schema::create('leave_request_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('from_amount')->default(0);
            $table->unsignedTinyInteger('to_amount')->default(0);
            $table->unsignedTinyInteger('day_in_advance')->default(0);
            $table->boolean('reason')->default(0);
            $table->boolean('attachment')->default(0);
            $table->json('contract_types');
            $table->json('roles');            
            $table->foreignId('user_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
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
        Schema::dropIfExists('leave_request_rules');
    }
};

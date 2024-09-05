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
        Schema::create('process_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('min')->default(0);
            $table->unsignedInteger('max')->default(0);
            $table->unsignedTinyInteger('request_in_advance')->default(0);
            $table->boolean('require_reason')->default(0);
            $table->boolean('require_attachment')->default(0);
            $table->json('contract_types');
            $table->json('approval_roles');
            $table->string('feature');        
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_approval_rules');
    }
};

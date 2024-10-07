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
            $table->unsignedTinyInteger('maximum_balance')->default(0)->nullable();
            $table->json('option')->nullable();
            $table->json('rules')->nullable();
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

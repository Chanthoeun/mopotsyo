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

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('photo')->nullable();
            $table->string('gender', 6);
            $table->date('date_of_birth')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('position')->nullable();
            $table->string('address')->nullable();
            $table->string('telephone')->nullable();
            $table->boolean('status')->default(0);
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('department_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('shift_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
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
        Schema::dropIfExists('profiles');
    }
};

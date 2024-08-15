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

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 20)->nullable();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('gender', 6);
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 3)->nullable();
            $table->string('email')->unique();
            $table->string('telephone')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('village_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('commune_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('district_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('province_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->string('photo')->nullable();
            $table->date('resign_date')->nullable();
            $table->boolean('status')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('restrict')->cascadeOnUpdate();
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
        Schema::dropIfExists('employees');
    }
};

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

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_id', 20)->nullable()->unique();
            $table->foreignId('member_type_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('gender', 6);
            $table->date('date_of_birth')->nullable();
            $table->foreignId('nationality_id')->nullable()->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->string('address')->nullable();
            $table->foreignId('village_id')->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('commune_id')->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('district_id')->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('province_id')->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->string('telephone')->nullable();
            $table->string('photo')->nullable();
            $table->boolean('status')->default(0);            
            $table->foreignId('account_id')->nullable()->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('interviewed_id')->nullable();
            $table->timestamp('interviewed_at')->nullable();
            $table->foreignId('verified_id')->nullable();
            $table->timestamp('verified_at')->nullable();            
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
        Schema::dropIfExists('members');
    }
};

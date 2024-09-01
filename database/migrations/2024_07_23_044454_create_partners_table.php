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

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('abbr', 2)->unique();
            $table->string('address');
            $table->foreignId('village_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('commune_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('district_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('province_id')->nullable()->constrained('locations')->onDelete('restrict')->cascadeOnUpdate();
            $table->string('map')->nullable();            
            $table->boolean('is_sale')->default(0);
            $table->boolean('is_active')->default(1);
            $table->foreignId('partner_type_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
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
        Schema::dropIfExists('partners');
    }
};

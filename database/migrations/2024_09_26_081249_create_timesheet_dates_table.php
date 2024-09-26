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

        Schema::create('timesheet_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('day', 3, 2)->default(0.00);
            $table->unsignedTinyInteger('type')->default(1);
            $table->text('remark')->nullable();
            $table->foreignId('timesheet_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::dropIfExists('timesheet_dates');
    }
};

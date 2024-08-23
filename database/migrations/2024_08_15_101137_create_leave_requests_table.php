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

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->morphs('leaverequestable');
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
        Schema::dropIfExists('leave_requests');
    }
};

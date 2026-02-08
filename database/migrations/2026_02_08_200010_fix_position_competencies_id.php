<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop and recreate the table with auto-increment ID
        Schema::dropIfExists('position_competencies');

        Schema::create('position_competencies', function (Blueprint $table) {
            $table->id(); // Auto-increment instead of UUID
            $table->uuid('position_id');
            $table->uuid('competency_id');
            $table->integer('weight')->default(1);
            $table->boolean('is_critical')->default(false);
            $table->integer('min_score')->default(3);
            $table->text('position_specific_criteria_tr')->nullable();
            $table->text('position_specific_criteria_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('position_id')->references('id')->on('job_positions')->onDelete('cascade');
            $table->foreign('competency_id')->references('id')->on('competencies')->onDelete('cascade');

            $table->unique(['position_id', 'competency_id']);
            $table->index('position_id');
            $table->index('competency_id');
            $table->index('is_critical');
        });
    }

    public function down(): void
    {
        // Recreate with UUID if needed
        Schema::dropIfExists('position_competencies');

        Schema::create('position_competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('position_id');
            $table->uuid('competency_id');
            $table->integer('weight')->default(1);
            $table->boolean('is_critical')->default(false);
            $table->integer('min_score')->default(3);
            $table->text('position_specific_criteria_tr')->nullable();
            $table->text('position_specific_criteria_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('position_id')->references('id')->on('job_positions')->onDelete('cascade');
            $table->foreign('competency_id')->references('id')->on('competencies')->onDelete('cascade');

            $table->unique(['position_id', 'competency_id']);
        });
    }
};

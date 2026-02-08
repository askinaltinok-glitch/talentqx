<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table linking positions to their required competencies
        Schema::create('position_competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('position_id');
            $table->uuid('competency_id');
            $table->integer('weight')->default(1); // Importance weight (1-10)
            $table->boolean('is_critical')->default(false); // Must-have competency
            $table->integer('min_score')->default(3); // Minimum acceptable score (1-5)
            $table->text('position_specific_criteria_tr')->nullable(); // Additional criteria for this position
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
        Schema::dropIfExists('position_competencies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ground-truth data for calibration validation.
     * Links interview predictions to actual hiring outcomes.
     */
    public function up(): void
    {
        Schema::create('interview_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Link to interview
            $table->uuid('form_interview_id');
            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->onDelete('cascade');

            // Hiring outcome
            $table->boolean('hired')->nullable()->comment('Was the candidate hired?');
            $table->boolean('started')->nullable()->comment('Did they actually start working?');

            // Retention metrics
            $table->boolean('still_employed_30d')->nullable()->comment('Still employed after 30 days?');
            $table->boolean('still_employed_90d')->nullable()->comment('Still employed after 90 days?');

            // Performance metrics (optional)
            $table->unsignedTinyInteger('performance_rating')->nullable()->comment('1-5 rating (null = not rated)');

            // Safety metrics (critical for safety-critical positions)
            $table->boolean('incident_flag')->nullable()->comment('Any safety incidents?');
            $table->text('incident_notes')->nullable()->comment('Incident details if any');

            // Metadata
            $table->string('outcome_source', 32)->default('admin')->comment('admin, client, self, api');
            $table->uuid('recorded_by')->nullable()->comment('Admin user who recorded this');
            $table->timestamp('recorded_at')->useCurrent();

            // Additional notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('form_interview_id');
            $table->index(['hired', 'started']);
            $table->index('incident_flag');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_outcomes');
    }
};

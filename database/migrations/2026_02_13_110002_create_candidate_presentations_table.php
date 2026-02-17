<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company Consumption Layer - Candidate Presentations
        // Tracks which candidates were presented to companies for which requests
        Schema::create('candidate_presentations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // References
            $table->uuid('talent_request_id');
            $table->foreign('talent_request_id')
                ->references('id')
                ->on('talent_requests')
                ->onDelete('cascade');

            $table->uuid('pool_candidate_id');
            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->onDelete('cascade');

            // Presentation tracking
            $table->timestamp('presented_at');

            // Status workflow: sent -> viewed -> rejected/interviewed -> hired
            $table->enum('presentation_status', [
                'sent',
                'viewed',
                'rejected',
                'interviewed',
                'hired'
            ])->default('sent');

            // Client feedback
            $table->text('client_feedback')->nullable();
            $table->tinyInteger('client_score')->nullable(); // 1-5
            $table->string('rejection_reason', 255)->nullable();

            // Interview tracking
            $table->timestamp('interview_scheduled_at')->nullable();
            $table->timestamp('interviewed_at')->nullable();

            // Hire tracking
            $table->timestamp('hired_at')->nullable();
            $table->date('start_date')->nullable();

            // Link to outcome for model health
            $table->uuid('interview_outcome_id')->nullable();
            $table->foreign('interview_outcome_id')
                ->references('id')
                ->on('interview_outcomes')
                ->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('talent_request_id');
            $table->index('pool_candidate_id');
            $table->index('presentation_status');
            $table->index('presented_at');

            // Prevent duplicate presentations
            $table->unique(['talent_request_id', 'pool_candidate_id'], 'unique_presentation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_presentations');
    }
};

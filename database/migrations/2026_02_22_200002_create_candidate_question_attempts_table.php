<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_question_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->uuid('question_set_id');
            $table->unsignedInteger('attempt_no')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('selection_snapshot_json')->nullable(); // position_code, country_code, locale at time of selection
            $table->json('answers_json')->nullable();            // [{question_id, answer_text, answered_at}]
            $table->json('score_json')->nullable();              // {dimension_scores, confidence, red_flags}
            $table->timestamps();

            $table->index(['candidate_id', 'question_set_id'], 'cqa_candidate_set');
            $table->foreign('candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
            $table->foreign('question_set_id')->references('id')->on('interview_question_sets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_question_attempts');
    }
};

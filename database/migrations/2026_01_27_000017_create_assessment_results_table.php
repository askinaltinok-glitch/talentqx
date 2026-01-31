<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->string('ai_model', 100)->nullable();
            $table->timestamp('analyzed_at')->nullable();

            // Scores
            $table->decimal('overall_score', 5, 2);
            $table->json('competency_scores'); // {code: {score, weight, weighted_score, feedback}}

            // Risk Analysis
            $table->json('risk_flags'); // [{code, severity, description, evidence}]
            $table->string('risk_level', 20)->nullable(); // low, medium, high, critical

            // Level & Classification
            $table->string('level_label', 50); // Basarisiz, Gelisime Acik, Yeterli, Iyi, Mukemmel
            $table->integer('level_numeric'); // 1-5

            // Development
            $table->json('development_plan'); // [{area, current_level, target_level, actions, priority}]
            $table->json('strengths'); // [string]
            $table->json('improvement_areas'); // [string]

            // Promotion
            $table->boolean('promotion_suitable')->default(false);
            $table->string('promotion_readiness', 30)->nullable(); // not_ready, needs_development, ready, highly_ready
            $table->text('promotion_notes')->nullable();

            // Raw AI Response
            $table->json('raw_ai_response')->nullable();
            $table->json('question_analyses')->nullable();

            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('assessment_sessions')->onDelete('cascade');

            $table->index('session_id');
            $table->index('overall_score');
            $table->index('risk_level');
            $table->index('level_label');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_interview_analyses', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('form_interview_id', 36);
            $table->string('ai_model', 100)->nullable();
            $table->string('ai_provider', 50)->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->json('competency_scores')->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('behavior_analysis')->nullable();
            $table->json('red_flag_analysis')->nullable();
            $table->json('culture_fit')->nullable();
            $table->json('decision_snapshot')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->json('question_analyses')->nullable();
            $table->string('scoring_method', 32)->default('ai');
            $table->unsignedInteger('token_usage_input')->nullable();
            $table->unsignedInteger('token_usage_output')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index('form_interview_id');
            $table->index('scoring_method');
            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_interview_analyses');
    }
};

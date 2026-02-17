<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('interview_id');
            $table->string('ai_model', 100)->nullable();
            $table->string('ai_model_version', 50)->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->json('competency_scores');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('behavior_analysis')->nullable();
            $table->json('red_flag_analysis')->nullable();
            $table->json('culture_fit')->nullable();
            $table->json('decision_snapshot')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->json('question_analyses')->nullable();
            $table->timestamps();

            $table->foreign('interview_id')->references('id')->on('interviews')->onDelete('cascade');

            $table->index('interview_id');
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_analyses');
    }
};

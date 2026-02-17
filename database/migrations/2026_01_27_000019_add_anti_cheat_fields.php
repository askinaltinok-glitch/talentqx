<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add anti-cheat fields to interview_analyses
        Schema::table('interview_analyses', function (Blueprint $table) {
            $table->decimal('cheating_risk_score', 5, 2)->nullable()->after('raw_ai_response');
            $table->json('cheating_flags')->nullable()->after('cheating_risk_score');
            $table->string('cheating_level', 20)->nullable()->after('cheating_flags'); // low, medium, high
            $table->json('timing_analysis')->nullable()->after('cheating_level');
            $table->json('similarity_analysis')->nullable()->after('timing_analysis');
            $table->json('consistency_analysis')->nullable()->after('similarity_analysis');
        });

        // Add timing data to interview_responses for analysis
        Schema::table('interview_responses', function (Blueprint $table) {
            $table->integer('thinking_pause_seconds')->nullable()->after('duration_seconds');
            $table->integer('speech_start_delay')->nullable()->after('thinking_pause_seconds');
            $table->decimal('words_per_minute', 6, 2)->nullable()->after('speech_start_delay');
            $table->integer('word_count')->nullable()->after('words_per_minute');
            $table->decimal('sentence_length_variance', 8, 4)->nullable()->after('word_count');
        });

        // Create response_similarities table for pattern detection
        Schema::create('response_similarities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('response_id_a');
            $table->uuid('response_id_b');
            $table->uuid('job_id');
            $table->integer('question_order');
            $table->decimal('cosine_similarity', 5, 4); // 0.0000 to 1.0000
            $table->decimal('jaccard_similarity', 5, 4)->nullable();
            $table->boolean('flagged')->default(false);
            $table->timestamps();

            $table->foreign('response_id_a')->references('id')->on('interview_responses')->onDelete('cascade');
            $table->foreign('response_id_b')->references('id')->on('interview_responses')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('cascade');

            $table->index(['job_id', 'question_order']);
            $table->index(['flagged']);
            $table->unique(['response_id_a', 'response_id_b']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_similarities');

        Schema::table('interview_responses', function (Blueprint $table) {
            $table->dropColumn([
                'thinking_pause_seconds',
                'speech_start_delay',
                'words_per_minute',
                'word_count',
                'sentence_length_variance',
            ]);
        });

        Schema::table('interview_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'cheating_risk_score',
                'cheating_flags',
                'cheating_level',
                'timing_analysis',
                'similarity_analysis',
                'consistency_analysis',
            ]);
        });
    }
};

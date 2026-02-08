<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Interview questions for each position
        // Standard: 8-10 questions per position
        Schema::create('position_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('position_id');
            $table->uuid('competency_id')->nullable(); // Which competency this question measures
            $table->string('question_type', 50)->default('behavioral'); // 'behavioral', 'situational', 'technical', 'experience'
            $table->text('question_tr');
            $table->text('question_en');
            $table->text('follow_up_tr')->nullable(); // Follow-up probe question
            $table->text('follow_up_en')->nullable();
            $table->json('expected_indicators')->nullable(); // What good answers should include
            $table->json('red_flag_indicators')->nullable(); // What bad answers contain
            $table->json('scoring_guide')->nullable(); // How to score 1-5
            $table->integer('difficulty_level')->default(2); // 1=Easy, 2=Medium, 3=Hard
            $table->integer('time_limit_seconds')->default(120); // Suggested response time
            $table->integer('sort_order')->default(0);
            $table->boolean('is_mandatory')->default(false); // Always include in interview
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('position_id')->references('id')->on('job_positions')->onDelete('cascade');
            $table->foreign('competency_id')->references('id')->on('competencies')->onDelete('set null');

            $table->index('position_id');
            $table->index('competency_id');
            $table->index('question_type');
            $table->index('is_mandatory');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_questions');
    }
};

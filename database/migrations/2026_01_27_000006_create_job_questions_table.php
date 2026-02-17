<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->integer('question_order');
            $table->string('question_type', 50);
            $table->text('question_text');
            $table->string('question_text_tts', 500)->nullable();
            $table->string('competency_code', 100)->nullable();
            $table->json('ideal_answer_points')->nullable();
            $table->json('scoring_rubric')->nullable();
            $table->integer('time_limit_seconds')->default(180);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('cascade');
            $table->index('job_id');
            $table->index(['job_id', 'question_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_questions');
    }
};

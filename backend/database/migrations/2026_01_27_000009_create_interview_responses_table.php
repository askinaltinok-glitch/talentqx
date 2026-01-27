<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('interview_id');
            $table->uuid('question_id');
            $table->integer('response_order');
            $table->string('video_segment_url', 500)->nullable();
            $table->string('audio_segment_url', 500)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->text('transcript')->nullable();
            $table->decimal('transcript_confidence', 5, 4)->nullable();
            $table->string('transcript_language', 10)->default('tr');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('interview_id')->references('id')->on('interviews')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('job_questions');

            $table->index('interview_id');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_responses');
    }
};

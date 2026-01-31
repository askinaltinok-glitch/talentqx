<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_answers')) {
            Schema::create('interview_answers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('session_id');
                $table->unsignedBigInteger('question_id');
                $table->string('question_key');
                $table->string('audio_path')->nullable();
                $table->text('raw_text')->nullable();
                $table->text('processed_text')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('session_id')->references('id')->on('interview_sessions')->onDelete('cascade');
                $table->foreign('question_id')->references('id')->on('interview_questions')->onDelete('cascade');
                $table->index('session_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_answers');
    }
};

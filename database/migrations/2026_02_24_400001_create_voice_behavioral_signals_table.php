<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_behavioral_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id')->index();
            $table->unsignedTinyInteger('question_slot'); // 1-12
            $table->unsignedSmallInteger('utterance_count');
            $table->unsignedSmallInteger('total_word_count');
            $table->decimal('total_duration_s', 8, 2);
            $table->decimal('avg_confidence', 5, 4);
            $table->decimal('min_confidence', 5, 4);
            $table->decimal('avg_wpm', 6, 1);
            $table->unsignedSmallInteger('total_pause_count');
            $table->unsignedSmallInteger('total_long_pause_count');
            $table->unsignedSmallInteger('total_filler_count');
            $table->decimal('avg_filler_ratio', 5, 4);
            $table->json('utterance_signals_json')->nullable();
            $table->timestamps();

            $table->unique(['form_interview_id', 'question_slot']);
            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_behavioral_signals');
    }
};

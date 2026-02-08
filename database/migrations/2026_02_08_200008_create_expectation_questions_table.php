<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standard expectation/satisfaction questions
        // These are position-agnostic and gather candidate expectations
        Schema::create('expectation_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('category', 50); // 'salary', 'work_hours', 'benefits', 'growth', 'culture', 'location'
            $table->text('question_tr');
            $table->text('question_en');
            $table->string('answer_type', 50)->default('open'); // 'open', 'single_choice', 'multi_choice', 'scale', 'numeric'
            $table->json('answer_options')->nullable(); // For choice-based questions
            $table->text('evaluation_note_tr')->nullable(); // How to interpret answers
            $table->text('evaluation_note_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expectation_questions');
    }
};

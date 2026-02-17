<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role_category', 100);
            $table->text('description')->nullable();
            $table->json('competencies'); // [{code, name, description, weight}]
            $table->json('red_flags'); // [{code, name, description, severity}]
            $table->json('questions'); // [{order, type, text, competency_code, options, correct_answer, scoring_rubric}]
            $table->json('scoring_config'); // {passing_score, level_thresholds, weights}
            $table->integer('time_limit_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('role_category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_templates');
    }
};

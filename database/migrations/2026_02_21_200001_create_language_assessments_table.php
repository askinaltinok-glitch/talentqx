<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');

            $table->string('assessment_language', 10)->default('en');

            // Self-declare
            $table->string('declared_level', 5)->nullable();
            $table->unsignedTinyInteger('declared_confidence')->nullable();

            // Micro test (objective)
            $table->unsignedTinyInteger('mcq_score')->nullable();
            $table->unsignedTinyInteger('mcq_total')->nullable();
            $table->unsignedTinyInteger('mcq_correct')->nullable();

            // Writing (rubric)
            $table->unsignedTinyInteger('writing_score')->nullable();
            $table->json('writing_rubric')->nullable();
            $table->text('writing_text')->nullable();

            // Interview verification (Phase-1/2)
            $table->unsignedTinyInteger('interview_score')->nullable();
            $table->json('interview_evidence')->nullable();

            // Final estimate
            $table->string('estimated_level', 5)->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->json('signals')->nullable();

            // Lock (admin)
            $table->string('locked_level', 5)->nullable();
            $table->uuid('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();

            $table->unique('candidate_id');
            $table->index(['estimated_level', 'confidence']);
            $table->foreign('candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_assessments');
    }
};

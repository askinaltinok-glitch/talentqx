<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_behavior_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id')->unique();
            $table->uuid('pool_candidate_id')->index();

            // 7 behavioral indices (0.000–1.000)
            $table->decimal('stress_index', 4, 3)->nullable();
            $table->decimal('confidence_index', 4, 3)->nullable();
            $table->decimal('decisiveness_index', 4, 3)->nullable();
            $table->decimal('hesitation_index', 4, 3)->nullable();
            $table->decimal('communication_clarity_index', 4, 3)->nullable();
            $table->decimal('emotional_stability_index', 4, 3)->nullable();
            $table->decimal('leadership_tone_index', 4, 3)->nullable();

            $table->decimal('overall_voice_score', 4, 3)->nullable();
            $table->json('computation_meta')->nullable();
            $table->timestamps();

            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->cascadeOnDelete();
            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_behavior_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_scoring_vectors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->decimal('technical_score', 5, 2)->nullable();
            $table->decimal('behavioral_score', 5, 2)->nullable();
            $table->decimal('reliability_score', 5, 2)->nullable();
            $table->decimal('personality_score', 5, 2)->nullable();
            $table->decimal('english_proficiency', 5, 2)->nullable();
            $table->string('english_level', 2)->nullable();
            $table->decimal('english_weight', 3, 2)->default(0.15);
            $table->decimal('composite_score', 5, 2)->nullable();
            $table->json('vector_json')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->string('version', 10)->default('v1');
            $table->timestamps();

            $table->unique(['candidate_id', 'version'], 'idx_candidate_version');
            $table->index('composite_score', 'idx_composite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_scoring_vectors');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Competency Dimensions
        Schema::create('competency_dimensions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('department', 30); // deck|engine|galley|ratings|all
            $table->json('description');       // {en, tr, ru, az}
            $table->decimal('weight_default', 4, 2)->default(0.15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Competency Questions
        Schema::create('competency_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('dimension_id');
            $table->string('role_scope', 50)->default('ALL');     // MASTER, CHIEF_OFFICER, AB, OILER, COOK, ALL
            $table->string('operation_scope', 10)->default('both'); // sea|river|both
            $table->string('vessel_scope', 30)->default('all');    // tanker|bulk|container|river|all
            $table->unsignedTinyInteger('difficulty')->default(1); // 1..3
            $table->json('question_text');                         // {en, tr, ru, az}
            $table->json('rubric');                                // scoring rubric per 0..5
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('dimension_id')
                ->references('id')
                ->on('competency_dimensions')
                ->onDelete('cascade');

            $table->index(['role_scope', 'is_active']);
            $table->index(['vessel_scope', 'operation_scope']);
        });

        // 3. Competency Assessments (append-only)
        Schema::create('competency_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->uuid('form_interview_id')->nullable();
            $table->timestamp('computed_at');
            $table->decimal('score_total', 5, 2);               // 0..100
            $table->json('score_by_dimension');                  // {DISCIPLINE: 72, LEADERSHIP: 65, ...}
            $table->json('flags');                               // ["low_discipline", ...]
            $table->json('evidence_summary');                    // {strengths: [...], concerns: [...], why_lines: [...]}
            $table->json('answer_scores')->nullable();           // per-question scores for audit
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->onDelete('cascade');

            $table->index(['pool_candidate_id', 'computed_at']);
        });

        // 4. Add competency columns to candidate_trust_profiles
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('competency_score')->nullable()->after('compliance_computed_at');
            $table->string('competency_status', 20)->nullable()->after('competency_score');
            $table->timestamp('competency_computed_at')->nullable()->after('competency_status');
            $table->index('competency_status');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->dropIndex(['competency_status']);
            $table->dropColumn(['competency_score', 'competency_status', 'competency_computed_at']);
        });

        Schema::dropIfExists('competency_assessments');
        Schema::dropIfExists('competency_questions');
        Schema::dropIfExists('competency_dimensions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) PUBLIC TOKEN SECURITY - Add fields to assessment_sessions
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->timestamp('used_at')->nullable()->after('token_expires_at');
            $table->integer('max_attempts')->default(3)->after('used_at');
            $table->integer('attempts_count')->default(0)->after('max_attempts');
            $table->boolean('one_time_use')->default(false)->after('attempts_count');
            $table->string('last_ip_address', 45)->nullable()->after('ip_address');
            $table->json('access_log')->nullable()->after('device_info'); // [{ip, timestamp, action}]
        });

        // 2) PROMPT VERSIONING - Create prompt_versions table
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100); // e.g., 'assessment_analysis', 'interview_analysis'
            $table->string('role_type', 100)->nullable(); // e.g., 'kasiyer', 'depo_sorumlusu'
            $table->integer('version')->default(1);
            $table->text('prompt_text');
            $table->json('variables')->nullable(); // list of placeholder variables
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->text('change_notes')->nullable();
            $table->timestamps();

            $table->unique(['name', 'role_type', 'version']);
            $table->index(['name', 'role_type', 'is_active']);
        });

        // 3) LLM VALIDATION & COST TRACKING - Add fields to assessment_results
        Schema::table('assessment_results', function (Blueprint $table) {
            // Analysis status
            $table->string('status', 30)->default('completed')->after('id'); // completed, analysis_failed, pending_retry

            // Validation fields
            $table->json('validation_errors')->nullable()->after('raw_ai_response');
            $table->integer('retry_count')->default(0)->after('validation_errors');
            $table->string('fallback_model', 100)->nullable()->after('retry_count');

            // Cost tracking
            $table->integer('input_tokens')->nullable()->after('ai_model');
            $table->integer('output_tokens')->nullable()->after('input_tokens');
            $table->decimal('cost_usd', 10, 6)->nullable()->after('output_tokens');
            $table->boolean('cost_limited')->default(false)->after('cost_usd');

            // Prompt versioning
            $table->uuid('used_prompt_version_id')->nullable()->after('cost_limited');

            // Anti-cheat for assessments
            $table->integer('cheating_risk_score')->nullable()->after('promotion_notes'); // 0-100
            $table->string('cheating_level', 20)->nullable()->after('cheating_risk_score'); // low, medium, high
            $table->json('cheating_flags')->nullable()->after('cheating_level');

            $table->index('status');
            $table->index('cost_limited');
            $table->index('cheating_level');
        });

        // 4) ASSESSMENT RESPONSE SIMILARITY - For anti-cheat
        Schema::create('assessment_response_similarities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_a_id');
            $table->uuid('session_b_id');
            $table->integer('question_order');
            $table->decimal('similarity_score', 5, 2); // 0-100
            $table->string('similarity_type', 30); // exact, near_duplicate, structural
            $table->boolean('flagged')->default(false);
            $table->timestamps();

            $table->foreign('session_a_id')->references('id')->on('assessment_sessions')->onDelete('cascade');
            $table->foreign('session_b_id')->references('id')->on('assessment_sessions')->onDelete('cascade');

            $table->index(['session_a_id', 'session_b_id']);
            $table->index(['similarity_score']);
            $table->index(['flagged']);
        });

        // 5) EMPLOYEE KVKK FIELDS
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_erased')->default(false)->after('status');
            $table->timestamp('erased_at')->nullable()->after('is_erased');
            $table->string('erasure_reason')->nullable()->after('erased_at');
            $table->integer('retention_days')->default(180)->after('erasure_reason');
        });

        // 6) EMPLOYEE DATA ERASURE REQUESTS
        Schema::create('employee_erasure_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('requested_by')->nullable();
            $table->string('request_type', 50); // employee_request, kvkk_request, retention_expired, company_policy
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->json('erased_data_types')->nullable(); // ['personal_info', 'assessments', 'scores', 'responses']
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index(['status']);
            $table->index(['request_type']);
        });

        // 7) COST CONFIG - Add to companies or create config table
        if (!Schema::hasColumn('companies', 'max_cost_per_session')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->decimal('max_cost_per_session', 8, 4)->default(0.50)->after('settings'); // $0.50 default
                $table->decimal('monthly_ai_budget', 10, 2)->nullable()->after('max_cost_per_session');
                $table->decimal('monthly_ai_spent', 10, 2)->default(0)->after('monthly_ai_budget');
            });
        }

        // 8) ASSESSMENT SESSIONS COST TRACKING
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->decimal('total_cost_usd', 10, 6)->nullable()->after('device_info');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'used_at', 'max_attempts', 'attempts_count', 'one_time_use',
                'last_ip_address', 'access_log', 'total_cost_usd'
            ]);
        });

        Schema::dropIfExists('prompt_versions');

        Schema::table('assessment_results', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'validation_errors', 'retry_count', 'fallback_model',
                'input_tokens', 'output_tokens', 'cost_usd', 'cost_limited',
                'used_prompt_version_id', 'cheating_risk_score', 'cheating_level', 'cheating_flags'
            ]);
        });

        Schema::dropIfExists('assessment_response_similarities');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['is_erased', 'erased_at', 'erasure_reason', 'retention_days']);
        });

        Schema::dropIfExists('employee_erasure_requests');

        if (Schema::hasColumn('companies', 'max_cost_per_session')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn(['max_cost_per_session', 'monthly_ai_budget', 'monthly_ai_spent']);
            });
        }
    }
};

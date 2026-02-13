<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Learning events - every outcome that triggers learning
        Schema::create('learning_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id');
            $table->string('model_version', 32);
            $table->string('industry_code', 64)->nullable();
            $table->string('source_channel', 64)->nullable();
            $table->integer('predicted_score');
            $table->integer('actual_score');
            $table->integer('error'); // actual - predicted
            $table->string('predicted_label', 16);
            $table->string('actual_label', 16);
            $table->boolean('is_false_positive')->default(false);
            $table->boolean('is_false_negative')->default(false);
            $table->json('feature_snapshot_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['model_version', 'created_at']);
            $table->index(['industry_code', 'created_at']);
            $table->index(['is_false_positive', 'created_at']);
            $table->index(['is_false_negative', 'created_at']);
        });

        // Learning patterns - accumulated FP/FN patterns
        Schema::create('learning_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('pattern_type', 32); // false_positive, false_negative
            $table->string('signal', 128); // e.g. risk_flag_underweighted:RF_AGGRESSION
            $table->string('industry_code', 64)->default('general');
            $table->integer('occurrence_count')->default(0);
            $table->timestamp('last_occurred_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['pattern_type', 'signal', 'industry_code']);
            $table->index(['pattern_type', 'occurrence_count']);
        });

        // Source channel metrics - success tracking per channel
        Schema::create('source_channel_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_channel', 64);
            $table->string('industry_code', 64)->default('general');
            $table->integer('total_outcomes')->default(0);
            $table->integer('successful_outcomes')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['source_channel', 'industry_code']);
            $table->index(['industry_code', 'total_outcomes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_channel_metrics');
        Schema::dropIfExists('learning_patterns');
        Schema::dropIfExists('learning_events');
    }
};

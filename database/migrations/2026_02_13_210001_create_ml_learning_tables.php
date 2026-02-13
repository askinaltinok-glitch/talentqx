<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feature importance tracking
        Schema::create('model_feature_importance', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('feature_name', 128);
            $table->string('industry_code', 64)->default('general');
            $table->integer('positive_impact_count')->default(0);
            $table->integer('negative_impact_count')->default(0);
            $table->decimal('correlation_score', 6, 4)->nullable();
            $table->decimal('current_weight', 6, 3)->default(0);
            $table->integer('sample_count')->default(0);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['feature_name', 'industry_code']);
            $table->index(['industry_code', 'correlation_score']);
        });

        // ML Diagnostics snapshots
        Schema::create('ml_diagnostics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model_version', 32);
            $table->string('industry_code', 64)->nullable();
            $table->date('snapshot_date');
            $table->integer('total_predictions');
            $table->integer('predictions_with_outcome');
            $table->decimal('false_hire_rate', 5, 2)->nullable(); // predicted good, actual bad %
            $table->decimal('false_reject_rate', 5, 2)->nullable(); // predicted bad, actual good %
            $table->decimal('hold_conversion_accuracy', 5, 2)->nullable();
            $table->decimal('mae', 6, 2)->nullable();
            $table->decimal('precision_at_50', 5, 2)->nullable();
            $table->json('high_score_failure_patterns_json')->nullable();
            $table->json('weight_changes_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['model_version', 'industry_code', 'snapshot_date']);
            $table->index(['snapshot_date']);
        });

        // Learning cycles log
        Schema::create('learning_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model_version_before', 32);
            $table->string('model_version_after', 32);
            $table->string('industry_code', 64)->nullable();
            $table->string('trigger', 64); // outcome, batch, manual
            $table->integer('samples_processed');
            $table->json('weight_deltas_json');
            $table->json('metrics_before_json')->nullable();
            $table->json('metrics_after_json')->nullable();
            $table->decimal('improvement_pct', 6, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['industry_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_cycles');
        Schema::dropIfExists('ml_diagnostics');
        Schema::dropIfExists('model_feature_importance');
    }
};

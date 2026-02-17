<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add status column to learning_events
        if (!Schema::hasColumn('learning_events', 'status')) {
            Schema::table('learning_events', function (Blueprint $table) {
                $table->string('status', 64)->default('applied')->after('is_false_negative');
                $table->index('status');
            });
        }

        // Add is_active column to model_weights
        if (!Schema::hasColumn('model_weights', 'is_active')) {
            Schema::table('model_weights', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('weights_json');
                $table->index('is_active');
            });
        }

        // Add assessment fields to model_features
        if (!Schema::hasColumn('model_features', 'english_score')) {
            Schema::table('model_features', function (Blueprint $table) {
                $table->unsignedTinyInteger('english_score')->nullable()->after('calibrated_score');
                $table->string('english_provider', 32)->nullable()->after('english_score');
                $table->boolean('video_present')->default(false)->after('english_provider');
                $table->string('video_provider', 32)->nullable()->after('video_present');
                $table->string('video_url')->nullable()->after('video_provider');
            });
        }

        // Add prediction_type to model_predictions
        if (!Schema::hasColumn('model_predictions', 'prediction_type')) {
            Schema::table('model_predictions', function (Blueprint $table) {
                $table->string('prediction_type', 32)->default('baseline')->after('explain_json');
                $table->text('prediction_reason')->nullable()->after('prediction_type');
                $table->index('prediction_type');
            });
        }

        // Create fairness reports table
        if (!Schema::hasTable('ml_fairness_reports')) {
            Schema::create('ml_fairness_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->date('report_date');
                $table->string('group_type', 32); // country_code, language, industry_code
                $table->string('group_value', 64);
                $table->integer('sample_count');
                $table->decimal('avg_predicted_score', 6, 2);
                $table->decimal('avg_outcome_score', 6, 2)->nullable();
                $table->decimal('hire_precision', 5, 2)->nullable();
                $table->boolean('has_alert')->default(false);
                $table->json('alert_details_json')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['report_date', 'group_type', 'group_value']);
                $table->index(['report_date', 'has_alert']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_fairness_reports');

        if (Schema::hasColumn('model_predictions', 'prediction_type')) {
            Schema::table('model_predictions', function (Blueprint $table) {
                $table->dropIndex(['prediction_type']);
                $table->dropColumn(['prediction_type', 'prediction_reason']);
            });
        }

        if (Schema::hasColumn('model_features', 'english_score')) {
            Schema::table('model_features', function (Blueprint $table) {
                $table->dropColumn([
                    'english_score',
                    'english_provider',
                    'video_present',
                    'video_provider',
                    'video_url',
                ]);
            });
        }

        if (Schema::hasColumn('model_weights', 'is_active')) {
            Schema::table('model_weights', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('learning_events', 'status')) {
            Schema::table('learning_events', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }
    }
};

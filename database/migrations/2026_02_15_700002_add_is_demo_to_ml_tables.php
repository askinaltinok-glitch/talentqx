<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // model_features
        Schema::table('model_features', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('answers_meta_json');
            $table->index('is_demo', 'idx_mf_is_demo');
        });

        DB::statement("
            UPDATE model_features mf
            JOIN form_interviews fi ON mf.form_interview_id = fi.id
            JOIN pool_candidates pc ON fi.pool_candidate_id = pc.id
            SET mf.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");

        // model_predictions
        Schema::table('model_predictions', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('prediction_reason');
            $table->index('is_demo', 'idx_mp_is_demo');
        });

        DB::statement("
            UPDATE model_predictions mp
            JOIN form_interviews fi ON mp.form_interview_id = fi.id
            JOIN pool_candidates pc ON fi.pool_candidate_id = pc.id
            SET mp.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");
    }

    public function down(): void
    {
        Schema::table('model_features', function (Blueprint $table) {
            $table->dropIndex('idx_mf_is_demo');
            $table->dropColumn('is_demo');
        });

        Schema::table('model_predictions', function (Blueprint $table) {
            $table->dropIndex('idx_mp_is_demo');
            $table->dropColumn('is_demo');
        });
    }
};

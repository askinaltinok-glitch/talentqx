<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // interview_outcomes
        Schema::table('interview_outcomes', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('notes');
            $table->index('is_demo', 'idx_io_is_demo');
        });

        DB::statement("
            UPDATE interview_outcomes io
            JOIN form_interviews fi ON io.form_interview_id = fi.id
            JOIN pool_candidates pc ON fi.pool_candidate_id = pc.id
            SET io.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");

        // candidate_notifications
        Schema::table('candidate_notifications', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('delivered_at');
            $table->index('is_demo', 'idx_cn_is_demo');
        });

        DB::statement("
            UPDATE candidate_notifications cn
            JOIN pool_candidates pc ON cn.pool_candidate_id = pc.id
            SET cn.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");

        // candidate_profile_views
        Schema::table('candidate_profile_views', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('context_meta');
            $table->index('is_demo', 'idx_cpv_is_demo');
        });

        DB::statement("
            UPDATE candidate_profile_views cpv
            JOIN pool_candidates pc ON cpv.pool_candidate_id = pc.id
            SET cpv.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");

        // vessel_reviews
        Schema::table('vessel_reviews', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('admin_notes');
            $table->index('is_demo', 'idx_vr_is_demo');
        });

        DB::statement("
            UPDATE vessel_reviews vr
            JOIN pool_candidates pc ON vr.pool_candidate_id = pc.id
            SET vr.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");
    }

    public function down(): void
    {
        Schema::table('interview_outcomes', function (Blueprint $table) {
            $table->dropIndex('idx_io_is_demo');
            $table->dropColumn('is_demo');
        });
        Schema::table('candidate_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_cn_is_demo');
            $table->dropColumn('is_demo');
        });
        Schema::table('candidate_profile_views', function (Blueprint $table) {
            $table->dropIndex('idx_cpv_is_demo');
            $table->dropColumn('is_demo');
        });
        Schema::table('vessel_reviews', function (Blueprint $table) {
            $table->dropIndex('idx_vr_is_demo');
            $table->dropColumn('is_demo');
        });
    }
};

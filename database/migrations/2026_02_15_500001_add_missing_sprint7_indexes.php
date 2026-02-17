<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monthly application limit query: WHERE pool_candidate_id = ? AND created_at >= ?
        Schema::table('maritime_job_applications', function (Blueprint $table) {
            $table->index(['pool_candidate_id', 'created_at'], 'idx_mja_candidate_created');
        });

        // Pending notification retry queries
        Schema::table('candidate_notifications', function (Blueprint $table) {
            $table->index('delivered_at', 'idx_cn_delivered_at');
        });

        // Stale push token cleanup
        Schema::table('candidate_push_tokens', function (Blueprint $table) {
            $table->index('last_seen_at', 'idx_cpt_last_seen_at');
        });

        // Batch membership expiry jobs
        Schema::table('candidate_memberships', function (Blueprint $table) {
            $table->index('expires_at', 'idx_cm_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('maritime_job_applications', function (Blueprint $table) {
            $table->dropIndex('idx_mja_candidate_created');
        });
        Schema::table('candidate_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_cn_delivered_at');
        });
        Schema::table('candidate_push_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_cpt_last_seen_at');
        });
        Schema::table('candidate_memberships', function (Blueprint $table) {
            $table->dropIndex('idx_cm_expires_at');
        });
    }
};

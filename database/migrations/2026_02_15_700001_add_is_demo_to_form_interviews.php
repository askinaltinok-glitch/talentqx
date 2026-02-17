<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('status');
            $table->index(['pool_candidate_id', 'is_demo'], 'idx_fi_candidate_demo');
        });

        // Backfill via pool_candidates
        DB::statement("
            UPDATE form_interviews fi
            JOIN pool_candidates pc ON fi.pool_candidate_id = pc.id
            SET fi.is_demo = 1
            WHERE pc.source_channel = 'demo'
        ");
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex('idx_fi_candidate_demo');
            $table->dropColumn('is_demo');
        });
    }
};

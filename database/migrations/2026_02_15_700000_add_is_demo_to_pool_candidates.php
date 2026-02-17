<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('status');
            $table->index('is_demo', 'idx_pc_is_demo');
        });

        // Backfill existing demo candidates
        DB::statement("UPDATE pool_candidates SET is_demo = 1 WHERE source_channel = 'demo'");
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropIndex('idx_pc_is_demo');
            $table->dropColumn('is_demo');
        });
    }
};

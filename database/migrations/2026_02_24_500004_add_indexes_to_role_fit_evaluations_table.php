<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_fit_evaluations', function (Blueprint $table) {
            // Composite index for retention cleanup queries (WHERE pool_candidate_id = ? AND created_at < ?)
            $table->index(['pool_candidate_id', 'created_at'], 'rfe_candidate_created_idx');

            // Index on created_at for time-range queries and retention sweeps
            $table->index('created_at', 'rfe_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('role_fit_evaluations', function (Blueprint $table) {
            $table->dropIndex('rfe_candidate_created_idx');
            $table->dropIndex('rfe_created_at_idx');
        });
    }
};

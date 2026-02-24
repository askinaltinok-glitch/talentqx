<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_fit_evaluations', function (Blueprint $table) {
            // Drop redundant standalone pool_candidate_id index —
            // composite (pool_candidate_id, created_at) covers leftmost prefix queries
            $table->dropIndex('role_fit_evaluations_pool_candidate_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('role_fit_evaluations', function (Blueprint $table) {
            $table->index('pool_candidate_id', 'role_fit_evaluations_pool_candidate_id_index');
        });
    }
};

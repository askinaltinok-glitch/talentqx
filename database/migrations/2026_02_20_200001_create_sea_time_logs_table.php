<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sea_time_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->uuid('candidate_contract_id');
            $table->uuid('vessel_id')->nullable();
            $table->string('rank_code', 50)->nullable();
            $table->date('original_start_date');
            $table->date('original_end_date')->nullable();
            $table->date('effective_start_date');
            $table->date('effective_end_date');
            $table->string('vessel_type', 50)->nullable();
            $table->string('operation_type', 10)->default('sea');
            $table->unsignedInteger('raw_days');
            $table->unsignedInteger('calculated_days');
            $table->unsignedInteger('overlap_deducted_days')->default(0);
            $table->uuid('computation_batch_id');
            $table->timestamp('computed_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->cascadeOnDelete();
            $table->foreign('candidate_contract_id')
                ->references('id')->on('candidate_contracts')
                ->cascadeOnDelete();
            $table->foreign('vessel_id')
                ->references('id')->on('vessels')
                ->nullOnDelete();

            $table->index(['pool_candidate_id', 'computation_batch_id'], 'stl_candidate_batch_idx');
            $table->index(['pool_candidate_id', 'vessel_type'], 'stl_candidate_vtype_idx');
            $table->index(['pool_candidate_id', 'rank_code'], 'stl_candidate_rank_idx');
            $table->index('computation_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sea_time_logs');
    }
};

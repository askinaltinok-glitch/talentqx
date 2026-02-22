<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('vessel_name', 150);
            $table->string('vessel_type', 50)->nullable();
            $table->string('company_name', 150);
            $table->string('rank', 50);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('trading_area', 100)->nullable();
            $table->string('dwt_range', 50)->nullable();
            $table->string('source', 20)->default('self_declared');
            $table->boolean('verified')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->cascadeOnDelete();
            $table->index('pool_candidate_id');
            $table->index(['pool_candidate_id', 'start_date']);
            $table->index('vessel_name');
            $table->index('company_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_contracts');
    }
};

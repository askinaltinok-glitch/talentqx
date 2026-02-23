<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_planning_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('vessel_id')->nullable()->index();
            $table->string('metric_type', 60); // time_to_fill_rank, availability_match_rate, contract_overlap_reduction
            $table->string('rank_code', 50)->nullable();
            $table->decimal('value', 10, 2);
            $table->json('meta')->nullable();
            $table->date('period_date'); // for time-series grouping
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'metric_type', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_planning_metrics');
    }
};

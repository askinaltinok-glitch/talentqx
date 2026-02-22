<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_risk_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vessel_id')->index();
            $table->string('fleet_type', 32)->nullable();
            $table->string('vessel_tier', 16);
            $table->unsignedInteger('crew_count');
            $table->decimal('avg_predictive_risk', 8, 4)->nullable();
            $table->decimal('avg_stability_index', 8, 4)->nullable();
            $table->decimal('avg_compliance_score', 8, 4)->nullable();
            $table->decimal('avg_competency_score', 8, 4)->nullable();
            $table->unsignedInteger('high_risk_count')->default(0);
            $table->unsignedInteger('critical_risk_count')->default(0);
            $table->json('detail_json')->nullable();
            $table->dateTime('computed_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['vessel_id', 'computed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_risk_snapshots');
    }
};

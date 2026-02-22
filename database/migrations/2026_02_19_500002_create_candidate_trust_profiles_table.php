<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_trust_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id')->unique();
            $table->decimal('cri_score', 5, 2)->default(0);
            $table->string('confidence_level', 10)->default('low');
            $table->decimal('short_contract_ratio', 5, 4)->default(0);
            $table->unsignedInteger('overlap_count')->default(0);
            $table->unsignedInteger('gap_months_total')->default(0);
            $table->boolean('rank_anomaly_flag')->default(false);
            $table->boolean('frequent_switch_flag')->default(false);
            $table->boolean('timeline_inconsistency_flag')->default(false);
            $table->json('detail_json')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_trust_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_risk_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id')->index();
            $table->dateTime('computed_at');
            $table->string('fleet_type', 32)->nullable();
            $table->json('inputs_json');
            $table->json('outputs_json');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->cascadeOnDelete();

            $table->index(['pool_candidate_id', 'computed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_risk_snapshots');
    }
};

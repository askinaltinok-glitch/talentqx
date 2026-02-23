<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vessel_id')->index();
            $table->uuid('candidate_id')->index();
            $table->string('rank_code', 50);
            $table->date('contract_start_at');
            $table->date('contract_end_at');
            $table->enum('status', ['planned', 'onboard', 'completed', 'terminated_early'])->default('planned');
            $table->string('termination_reason')->nullable();
            $table->dateTime('ended_early_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('vessel_id')->references('id')->on('fleet_vessels')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
            $table->unique(['vessel_id', 'candidate_id', 'contract_start_at'], 'vessel_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_assignments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_room_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fleet_vessel_id')->index();
            $table->uuid('vessel_id')->nullable()->index();
            $table->uuid('company_id')->index();
            $table->uuid('user_id')->index();
            $table->string('rank_code', 50);
            $table->string('action', 30); // shortlisted|selected|confirmed|rejected|deferred
            $table->uuid('candidate_id')->index();
            $table->string('candidate_name', 255);
            $table->json('compatibility_snapshot')->nullable();
            $table->json('risk_snapshot')->nullable();
            $table->json('simulation_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['fleet_vessel_id', 'rank_code', 'created_at'], 'drh_vessel_rank_created');

            $table->foreign('fleet_vessel_id')->references('id')
                ->on('fleet_vessels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_room_history');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_command_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->json('raw_identity_answers')->nullable();
            $table->json('vessel_experience')->nullable();
            $table->json('dwt_history')->nullable();
            $table->json('automation_exposure')->nullable();
            $table->json('cargo_history')->nullable();
            $table->json('trading_areas')->nullable();
            $table->json('crew_scale_history')->nullable();
            $table->json('incident_history')->nullable();
            $table->string('derived_command_class', 30)->nullable()->index();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('multi_class_flags')->nullable();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_command_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captain_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->unique();
            $table->json('style_vector_json');
            $table->json('command_profile_json');
            $table->json('evidence_counts_json');
            $table->decimal('confidence', 3, 2)->default(0);
            $table->dateTime('last_computed_at')->nullable();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')
                ->on('pool_candidates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captain_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_manning_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vessel_id')->index();
            $table->string('rank_code', 50);
            $table->unsignedSmallInteger('required_count')->default(1);
            $table->json('required_certs')->nullable();
            $table->string('min_english_level', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('vessel_id')->references('id')->on('fleet_vessels')->cascadeOnDelete();
            $table->unique(['vessel_id', 'rank_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_manning_requirements');
    }
};

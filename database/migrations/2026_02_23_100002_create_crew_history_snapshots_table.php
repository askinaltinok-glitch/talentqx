<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_history_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vessel_id')->index();
            $table->date('snapshot_date');
            $table->json('crew_roster');
            $table->json('dimension_averages');
            $table->float('avg_synergy_score')->nullable();
            $table->unsignedInteger('crew_count');
            $table->string('trigger', 30)->default('scheduled');
            $table->timestamps();

            $table->unique(['vessel_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_history_snapshots');
    }
};

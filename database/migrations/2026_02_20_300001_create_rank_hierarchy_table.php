<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rank_hierarchy', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_code', 16);         // MASTER, C/O, 2/O, etc. (from RankProgressionAnalyzer)
            $table->string('stcw_rank_code', 32);          // master, chief_officer, etc. (from StcwRequirementSeeder)
            $table->string('department', 16);              // deck, engine, electrical, catering
            $table->unsignedTinyInteger('level');           // 1-8, matches RANK_LADDER
            $table->unsignedSmallInteger('min_sea_months_in_rank')->default(0);
            // Minimum months at THIS rank before eligible for next rank promotion
            $table->unsignedSmallInteger('min_total_sea_months')->default(0);
            // Minimum total sea service months to hold this rank
            $table->string('next_rank_code', 16)->nullable();
            // Canonical code of the next rank in ladder (null = top rank)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('canonical_code');
            $table->index('department');
            $table->index('stcw_rank_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rank_hierarchy');
    }
};

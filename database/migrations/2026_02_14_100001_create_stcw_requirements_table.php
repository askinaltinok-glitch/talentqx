<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stcw_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('rank_code', 32);
            $table->string('department', 16); // deck, engine, hotel, other
            $table->string('vessel_type', 32)->default('any');
            // any, tanker, passenger, cargo, offshore

            $table->json('required_certificates');
            // Array of certificate_type codes required for this rank

            $table->boolean('mandatory')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('rank_code');
            $table->index('department');
            $table->index(['rank_code', 'vessel_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stcw_requirements');
    }
};

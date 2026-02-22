<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('imo', 20)->unique();
            $table->string('name', 150);
            $table->string('type', 50)->nullable();
            $table->string('flag', 5)->nullable();
            $table->unsignedInteger('dwt')->nullable();
            $table->unsignedInteger('gt')->nullable();
            $table->decimal('length_m', 8, 2)->nullable();
            $table->decimal('beam_m', 8, 2)->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->string('data_source', 30)->default('manual');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('type');
            $table->index('flag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};

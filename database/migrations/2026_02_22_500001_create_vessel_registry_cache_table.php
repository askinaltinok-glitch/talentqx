<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_registry_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('imo')->unique();
            $table->string('name')->nullable();
            $table->string('flag', 80)->nullable();
            $table->string('vessel_type', 100)->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->unsignedInteger('dwt')->nullable();
            $table->unsignedInteger('gt')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->enum('source', ['manual', 'import', 'api'])->default('manual');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_registry_cache');
    }
};

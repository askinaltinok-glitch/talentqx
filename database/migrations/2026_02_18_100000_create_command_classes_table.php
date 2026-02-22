<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('name_en', 100);
            $table->string('name_tr', 100);
            $table->json('vessel_types');
            $table->unsignedInteger('dwt_min')->default(0);
            $table->unsignedInteger('dwt_max')->default(0);
            $table->json('trading_areas');
            $table->json('automation_levels');
            $table->unsignedSmallInteger('crew_min')->default(0);
            $table->unsignedSmallInteger('crew_max')->default(0);
            $table->json('cargo_types');
            $table->json('risk_profile');
            $table->json('certifications_required');
            $table->json('weight_vector');
            $table->json('special_considerations')->nullable();
            $table->json('sub_classes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_classes');
    }
};

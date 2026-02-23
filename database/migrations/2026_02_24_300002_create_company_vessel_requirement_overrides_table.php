<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_vessel_requirement_overrides', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            $table->string('vessel_type_key', 50);
            $table->json('overrides_json');
            $table->timestamps();

            $table->unique(['company_id', 'vessel_type_key'], 'company_vessel_type_unique');
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_vessel_requirement_overrides');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_requirement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('vessel_type_key', 50)->unique();
            $table->string('label', 100);
            $table->json('profile_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_requirement_templates');
    }
};

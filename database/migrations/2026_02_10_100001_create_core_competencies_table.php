<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TalentQX Karar Motoru - Temel Yetkinlikler Tablosu
     * 8 core competency for AI scoring engine
     */
    public function up(): void
    {
        Schema::create('core_competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_tr', 100);
            $table->string('name_en', 100);
            $table->text('description_tr');
            $table->text('description_en')->nullable();
            $table->string('category', 50)->default('core'); // core, role_specific
            $table->integer('weight')->default(10); // percentage weight in scoring
            $table->json('measurable_signals'); // positive indicators array
            $table->json('negative_signals'); // negative indicators array
            $table->json('scoring_rubric'); // 1-5 scale definitions {"5": "...", "4": "...", etc}
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_competencies');
    }
};

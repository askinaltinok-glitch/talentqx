<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('red_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_tr', 100);
            $table->string('name_en', 100);
            $table->string('severity', 20); // critical, high, medium, low
            $table->text('description_tr');
            $table->text('description_en')->nullable();
            $table->json('trigger_phrases'); // phrases that trigger this flag
            $table->json('behavioral_patterns'); // behavioral patterns to detect
            $table->string('detection_method', 50)->default('phrase_match'); // phrase_match, pattern_analysis, cross_reference
            $table->json('impact'); // score impacts when detected
            $table->text('analysis_note_tr'); // note to include in analysis
            $table->text('analysis_note_en')->nullable();
            $table->boolean('causes_auto_reject')->default(false);
            $table->integer('max_score_override')->nullable(); // if set, caps overall score
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('severity');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('red_flags');
    }
};

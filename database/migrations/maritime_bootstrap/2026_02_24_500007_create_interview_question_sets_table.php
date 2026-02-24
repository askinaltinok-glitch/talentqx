<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_question_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64);           // e.g. "maritime_behavioral_v2"
            $table->string('version', 16);         // e.g. "1.0"
            $table->string('industry_code', 32)->default('maritime');
            $table->string('position_code', 64);   // e.g. "MASTER", "C/O", "__generic__"
            $table->string('country_code', 8)->nullable(); // e.g. "TR", null = generic
            $table->string('locale', 8);           // e.g. "tr", "en", "ru"
            $table->boolean('is_active')->default(true);
            $table->json('rules_json')->nullable(); // difficulty scaling, dimension coverage rules
            $table->json('questions_json');          // array of 12 question objects
            $table->timestamps();

            $table->unique(['code', 'version', 'locale', 'position_code', 'country_code'], 'iq_set_unique');
            $table->index(['industry_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_question_sets');
    }
};

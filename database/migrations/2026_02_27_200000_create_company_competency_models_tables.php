<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Company competency models
        Schema::create('company_competency_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->unique(['company_id', 'name']);
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        // 2) Model items (selected competencies + weights)
        Schema::create('company_competency_model_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('model_id');
            $table->string('competency_code', 50);
            $table->decimal('weight', 5, 2);
            $table->enum('priority', ['critical', 'important', 'nice_to_have'])->default('important');
            $table->unsignedTinyInteger('min_score')->nullable();
            $table->timestamps();

            $table->index('model_id');
            $table->unique(['model_id', 'competency_code']);
            $table->foreign('model_id')->references('id')->on('company_competency_models')->cascadeOnDelete();
        });

        // 3) Add columns to form_interviews
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->decimal('company_fit_score', 5, 2)->nullable()->after('final_score');
            $table->json('company_competency_scores')->nullable()->after('company_fit_score');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn(['company_fit_score', 'company_competency_scores']);
        });

        Schema::dropIfExists('company_competency_model_items');
        Schema::dropIfExists('company_competency_models');
    }
};

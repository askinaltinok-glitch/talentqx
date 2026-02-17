<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id')->unique();
            $table->string('industry_code', 64)->nullable()->index();
            $table->string('position_code', 128);
            $table->string('language', 8)->default('tr');
            $table->string('country_code', 2)->nullable();
            $table->string('source_channel', 64)->nullable();
            $table->json('competency_scores_json')->nullable();
            $table->json('risk_flags_json')->nullable();
            $table->integer('raw_final_score')->nullable();
            $table->integer('calibrated_score')->nullable();
            $table->decimal('z_score', 6, 3)->nullable();
            $table->string('policy_decision', 32)->nullable();
            $table->string('policy_code', 64)->nullable();
            $table->string('template_json_sha256', 64)->nullable();
            $table->json('answers_meta_json')->nullable();
            $table->timestamps();

            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->onDelete('cascade');

            $table->index(['industry_code', 'position_code', 'created_at']);
            $table->index(['source_channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_features');
    }
};

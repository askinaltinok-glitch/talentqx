<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('template_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type', 50)->nullable();
            $table->integer('experience_years')->default(0);
            $table->json('competencies')->nullable();
            $table->json('red_flags')->nullable();
            $table->json('question_rules')->nullable();
            $table->json('scoring_rubric')->nullable();
            $table->json('interview_settings')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('position_templates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->unique(['company_id', 'slug']);
            $table->index('company_id');
            $table->index('status');
            $table->index('template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};

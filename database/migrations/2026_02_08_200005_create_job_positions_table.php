<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Specific job positions within subdomains
        // e.g., "Senior Software Developer" under IT -> Software Development
        Schema::create('job_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subdomain_id');
            $table->uuid('archetype_id')->nullable();
            $table->string('code', 150)->unique(); // e.g., 'IT_SOFTDEV_SENIOR_DEV'
            $table->string('name_tr');
            $table->string('name_en');
            $table->text('description_tr')->nullable();
            $table->text('description_en')->nullable();
            $table->text('responsibilities_tr')->nullable(); // Key responsibilities
            $table->text('responsibilities_en')->nullable();
            $table->text('requirements_tr')->nullable(); // Requirements/qualifications
            $table->text('requirements_en')->nullable();
            $table->integer('experience_min_years')->default(0);
            $table->integer('experience_max_years')->nullable();
            $table->string('education_level', 50)->nullable(); // 'high_school', 'bachelor', 'master', etc.
            $table->json('keywords')->nullable(); // Search keywords for matching
            $table->json('scoring_rubric')->nullable(); // Position-specific scoring rules
            $table->json('critical_behaviors')->nullable(); // Must-have behaviors
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('subdomain_id')->references('id')->on('job_subdomains')->onDelete('cascade');
            $table->foreign('archetype_id')->references('id')->on('role_archetypes')->onDelete('set null');

            $table->index('subdomain_id');
            $table->index('archetype_id');
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_positions');
    }
};

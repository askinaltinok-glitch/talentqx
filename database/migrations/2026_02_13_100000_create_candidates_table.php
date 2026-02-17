<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pool candidates = Candidate Supply Engine
        // Separate from ATS Candidate model (company-specific)
        Schema::create('pool_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Personal info
            $table->string('first_name', 128);
            $table->string('last_name', 128);
            $table->string('email', 255)->unique();
            $table->string('phone', 32)->nullable();
            $table->char('country_code', 2);
            $table->string('preferred_language', 8)->default('tr');

            // Self-assessment
            $table->string('english_level_self', 4)->nullable(); // A1, A2, B1, B2, C1, C2

            // Acquisition tracking
            $table->string('source_channel', 64);
            // linkedin, referral, maritime_event, job_board, organic, company_invite
            $table->json('source_meta')->nullable();

            // Status management
            $table->string('status', 32)->default('new');
            // new, assessed, in_pool, presented_to_company, hired, archived

            // Industry classification
            $table->string('primary_industry', 64)->default('general');
            // general, maritime, retail, logistics, hospitality

            // Maritime-specific flags
            $table->boolean('seafarer')->default(false);
            $table->boolean('english_assessment_required')->default(false);
            $table->boolean('video_assessment_required')->default(false);

            // Timestamps
            $table->timestamp('last_assessed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('primary_industry');
            $table->index('source_channel');
            $table->index(['status', 'primary_industry']);
            $table->index('last_assessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_candidates');
    }
};

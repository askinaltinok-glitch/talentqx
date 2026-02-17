<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company Consumption Layer - Talent Requests
        // Represents a company's request for candidates from the pool
        Schema::create('talent_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Company reference
            $table->uuid('pool_company_id');
            $table->foreign('pool_company_id')
                ->references('id')
                ->on('pool_companies')
                ->onDelete('cascade');

            // Position requirements
            $table->string('position_code', 128);
            $table->string('industry_code', 64)->default('general');
            $table->integer('required_count')->default(1);

            // Candidate requirements
            $table->boolean('english_required')->default(false);
            $table->string('min_english_level', 4)->nullable(); // A1, A2, B1, B2, C1, C2
            $table->integer('experience_years')->nullable();
            $table->integer('min_score')->nullable(); // Minimum assessment score
            $table->json('required_competencies')->nullable();

            // Additional info
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            // Status workflow: open -> matching -> fulfilled -> closed
            $table->enum('status', ['open', 'matching', 'fulfilled', 'closed'])->default('open');

            // Tracking
            $table->integer('presented_count')->default(0);
            $table->integer('hired_count')->default(0);
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('pool_company_id');
            $table->index('status');
            $table->index('industry_code');
            $table->index('position_code');
            $table->index(['status', 'industry_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_requests');
    }
};

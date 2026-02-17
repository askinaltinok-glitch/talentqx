<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maritime_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_company_id');
            $table->string('vessel_type', 64)->nullable();
            $table->string('rank', 64);
            $table->string('salary_range', 128)->nullable();
            $table->string('contract_length', 64)->nullable();
            $table->string('rotation', 64)->nullable();
            $table->string('internet_policy', 128)->nullable();
            $table->string('bonus_policy', 128)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('pool_company_id')
                ->references('id')
                ->on('pool_companies')
                ->cascadeOnDelete();

            $table->index(['is_active', 'rank']);
            $table->index(['is_active', 'vessel_type']);
            $table->index('pool_company_id');
        });

        Schema::create('maritime_job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('maritime_job_id');
            $table->uuid('pool_candidate_id');
            $table->string('status', 32)->default('applied'); // applied, shortlisted, rejected, hired
            $table->timestamps();

            $table->foreign('maritime_job_id')
                ->references('id')
                ->on('maritime_jobs')
                ->cascadeOnDelete();

            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->cascadeOnDelete();

            $table->unique(['maritime_job_id', 'pool_candidate_id'], 'uq_job_candidate');
            $table->index(['pool_candidate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maritime_job_applications');
        Schema::dropIfExists('maritime_jobs');
    }
};

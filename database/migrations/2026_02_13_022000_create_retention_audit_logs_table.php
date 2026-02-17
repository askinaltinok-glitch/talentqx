<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Run metadata
            $table->timestamp('run_at');
            $table->boolean('dry_run')->default(false);
            $table->string('triggered_by', 64)->default('scheduler'); // scheduler, manual, api

            // Counts
            $table->integer('interviews_deleted')->default(0);
            $table->integer('interviews_anonymized')->default(0);
            $table->integer('answers_deleted')->default(0);
            $table->integer('consents_deleted')->default(0);

            // Errors
            $table->integer('errors_count')->default(0);
            $table->json('error_details')->nullable();

            // Duration
            $table->integer('duration_seconds')->nullable();

            $table->timestamps();

            // Index for querying recent runs
            $table->index('run_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_audit_logs');
    }
};

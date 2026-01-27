<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add retention_days to job_postings (if not exists)
        if (!Schema::hasColumn('job_postings', 'retention_days')) {
            Schema::table('job_postings', function (Blueprint $table) {
                $table->integer('retention_days')->default(180)->after('closes_at');
            });
        }

        // Add KVKK fields to existing audit_logs table
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'metadata')) {
                    $table->json('metadata')->nullable()->after('new_values');
                }
                if (!Schema::hasColumn('audit_logs', 'erased_by_request')) {
                    $table->boolean('erased_by_request')->default(false)->after('user_agent');
                }
                if (!Schema::hasColumn('audit_logs', 'erasure_reason')) {
                    $table->string('erasure_reason')->nullable()->after('erased_by_request');
                }
            });
        } else {
            // Create audit_logs table for KVKK compliance
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('action', 100);
                $table->string('entity_type', 100);
                $table->uuid('entity_id')->nullable();
                $table->uuid('user_id')->nullable();
                $table->uuid('company_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->boolean('erased_by_request')->default(false);
                $table->string('erasure_reason')->nullable();
                $table->timestamps();

                $table->index(['entity_type', 'entity_id']);
                $table->index(['user_id']);
                $table->index(['company_id']);
                $table->index(['action']);
                $table->index(['created_at']);
                $table->index(['erased_by_request']);
            });
        }

        // Create data_erasure_requests table
        Schema::create('data_erasure_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->uuid('requested_by')->nullable(); // user who requested
            $table->string('request_type', 50); // candidate_request, kvkk_request, retention_expired
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->json('erased_data_types')->nullable(); // ['personal_info', 'videos', 'transcripts', 'scores']
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');
            $table->index(['status']);
            $table->index(['request_type']);
        });

        // Add soft delete and erasure tracking to candidates
        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('is_erased')->default(false)->after('status');
            $table->timestamp('erased_at')->nullable()->after('is_erased');
            $table->string('erasure_reason')->nullable()->after('erased_at');
        });

        // Add erasure tracking to interviews
        Schema::table('interviews', function (Blueprint $table) {
            $table->boolean('media_erased')->default(false)->after('status');
            $table->timestamp('media_erased_at')->nullable()->after('media_erased');
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['media_erased', 'media_erased_at']);
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['is_erased', 'erased_at', 'erasure_reason']);
        });

        Schema::dropIfExists('data_erasure_requests');
        Schema::dropIfExists('audit_logs');

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('retention_days');
        });
    }
};

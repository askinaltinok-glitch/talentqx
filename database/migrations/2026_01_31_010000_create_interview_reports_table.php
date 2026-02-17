<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_reports')) {
            Schema::create('interview_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('session_id');
                $table->string('tenant_id')->nullable();
                $table->string('locale', 5)->default('tr');
                $table->string('status')->default('pending');
                $table->string('storage_disk')->default('private');
                $table->string('storage_path')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('checksum')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->foreign('session_id')->references('id')->on('interview_sessions')->onDelete('cascade');
                $table->index(['session_id', 'status']);
                $table->index('tenant_id');
            });
        }

        if (!Schema::hasTable('report_audit_logs')) {
            Schema::create('report_audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('report_id');
                $table->string('action');
                $table->string('actor_type')->nullable();
                $table->string('actor_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('report_id')->references('id')->on('interview_reports')->onDelete('cascade');
                $table->index(['report_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_audit_logs');
        Schema::dropIfExists('interview_reports');
    }
};

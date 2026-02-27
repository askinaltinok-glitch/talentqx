<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('interview_id')->nullable()->index();
            $table->uuid('candidate_id')->nullable()->index();
            $table->string('action', 40);           // approve|reject|override|download_packet
            $table->uuid('performed_by')->nullable(); // admin user_id
            $table->string('old_state', 40)->nullable();
            $table->string('new_state', 40)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();     // ip, user_agent, filename, etc.
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for common queries
            $table->index(['candidate_id', 'action']);
            $table->index(['performed_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_audit_logs');
    }
};

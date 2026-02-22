<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_reminder_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->uuid('credential_id')->nullable();
            $table->string('reminder_type', 50); // credential_expiry_60, credential_expiry_30, credential_expiry_7, credential_expiry_1
            $table->string('channel', 20)->default('email');
            $table->string('to', 190);
            $table->string('language', 10);
            $table->string('status', 20)->default('queued'); // queued, sent, failed, blocked_safety
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
            // No updated_at — append-only

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->onDelete('cascade');

            $table->foreign('credential_id')
                ->references('id')->on('candidate_credentials')
                ->onDelete('set null');

            $table->index(['pool_candidate_id', 'reminder_type']);
            $table->index(['credential_id', 'reminder_type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_reminder_logs');
    }
};

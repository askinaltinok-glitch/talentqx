<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->uuid('form_interview_id')->nullable();
            $table->string('invitation_token', 128)->unique();
            $table->string('invitation_token_hash', 64)->index();
            $table->enum('status', ['invited', 'started', 'completed', 'expired'])->default('invited');
            $table->timestamp('invited_at');
            $table->timestamp('expires_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->unsignedInteger('access_count')->default(0);
            $table->string('ip_started', 45)->nullable();
            $table->string('locale', 5)->default('en');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
            $table->foreign('form_interview_id')->references('id')->on('form_interviews')->nullOnDelete();

            $table->index(['pool_candidate_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_invitations');
    }
};

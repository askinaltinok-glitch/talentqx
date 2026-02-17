<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Requesting company/user
            $table->foreignUuid('requesting_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('requesting_user_id')->constrained('users')->cascadeOnDelete();

            // Target candidate (owned by another company)
            $table->foreignUuid('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignUuid('owning_company_id')->constrained('companies')->cascadeOnDelete();

            // Request details
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, expired
            $table->text('request_message')->nullable();
            $table->text('response_message')->nullable();

            // Access token for owner to approve/reject
            $table->string('access_token', 64)->unique();
            $table->timestamp('token_expires_at');

            // Timestamps
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['requesting_company_id', 'status']);
            $table->index(['owning_company_id', 'status']);
            $table->index(['candidate_id', 'requesting_company_id']);
            $table->index('access_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_access_requests');
    }
};

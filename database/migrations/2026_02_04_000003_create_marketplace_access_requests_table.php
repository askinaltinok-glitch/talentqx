<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requesting_company_id');
            $table->uuid('requesting_user_id');
            $table->uuid('candidate_id');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, expired
            $table->text('request_message')->nullable();
            $table->text('response_message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('approval_token', 64)->unique();
            $table->timestamp('token_expires_at');
            $table->timestamps();

            // Foreign keys
            $table->foreign('requesting_company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('requesting_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('candidate_id')
                ->references('id')
                ->on('candidates')
                ->onDelete('cascade');

            // Indexes
            $table->index('approval_token');
            $table->index('status');
            $table->index(['requesting_company_id', 'candidate_id'], 'idx_company_candidate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_access_requests');
    }
};

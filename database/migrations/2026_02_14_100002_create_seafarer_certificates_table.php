<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seafarer_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->onDelete('cascade');

            $table->string('certificate_type', 32);
            // Maps to certificate_types.code

            $table->string('certificate_code', 64)->nullable();
            // The actual certificate number/code issued

            $table->string('issuing_authority', 128)->nullable();
            $table->char('issuing_country', 2)->nullable();

            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();

            $table->string('document_url', 512)->nullable();
            $table->string('document_hash', 64)->nullable();
            // SHA-256 hash for document integrity

            $table->string('verification_status', 16)->default('pending');
            // pending, verified, rejected, expired

            $table->text('verification_notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('pool_candidate_id');
            $table->index('certificate_type');
            $table->index('verification_status');
            $table->index('expires_at');
            $table->index(['pool_candidate_id', 'certificate_type']);
            $table->index(['verification_status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seafarer_certificates');
    }
};

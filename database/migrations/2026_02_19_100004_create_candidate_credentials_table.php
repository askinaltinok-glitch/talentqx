<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('credential_type', 50); // STCW, GMDSS, Tanker Familiarization, Medical, Passport, Seaman Book
            $table->string('credential_number', 100)->nullable();
            $table->string('issuer', 100)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('file_url', 500)->nullable();
            $table->string('verification_status', 20)->default('self_declared'); // unverified, self_declared, verified
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->onDelete('cascade');

            $table->index('pool_candidate_id');
            $table->index('expires_at');
            $table->index('credential_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_credentials');
    }
};

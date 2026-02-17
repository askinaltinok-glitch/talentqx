<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Link to form interview (nullable for standalone consents)
            $table->uuid('form_interview_id')->nullable()->index();

            // Consent type: data_processing, data_retention, data_sharing, marketing
            $table->string('consent_type', 64);

            // Consent text version (e.g., 'v1.0', 'kvkk-2024-01')
            $table->string('consent_version', 32);

            // Jurisdiction/regulation
            $table->string('regulation', 16)->default('KVKK'); // KVKK, GDPR, etc.

            // Consent state
            $table->boolean('granted')->default(false);

            // Collection metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('collection_method', 32)->default('web_form'); // web_form, api, import

            // Timestamps
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['form_interview_id', 'consent_type']);
            $table->index(['consent_type', 'granted']);
            $table->index('consented_at');

            // FK
            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_consents');
    }
};

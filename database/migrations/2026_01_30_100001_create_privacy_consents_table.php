<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Subject identification (polymorphic-style)
            $table->enum('subject_type', ['lead', 'employee', 'user', 'visitor'])->default('visitor');
            $table->uuid('subject_id')->nullable();

            // Contact info (for visitors without account)
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('full_name')->nullable();

            // Consent details
            $table->enum('consent_type', ['privacy_notice', 'marketing', 'data_processing'])->default('privacy_notice');
            $table->enum('regime', ['KVKK', 'GDPR', 'GLOBAL'])->default('GLOBAL');
            $table->string('policy_version', 20)->default('2026-01');
            $table->string('locale', 5)->default('en');

            // Geo/Device info
            $table->string('country', 2)->nullable(); // ISO 3166-1 alpha-2
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            $table->text('user_agent')->nullable();

            // Source tracking
            $table->string('source', 50)->default('website'); // demo_form, contact, employee_assessment, etc.
            $table->string('form_type', 50)->nullable(); // sales, demo, contact, support

            // Timestamps
            $table->timestamp('accepted_at')->useCurrent();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['subject_type', 'subject_id']);
            $table->index('email');
            $table->index('regime');
            $table->index('source');
            $table->index('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_consents');
    }
};

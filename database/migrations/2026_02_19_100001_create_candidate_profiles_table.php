<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('status', 20)->default('seeker'); // seeker, passive, blocked
            $table->string('preferred_language', 10)->default('en');
            $table->string('timezone', 50)->nullable();

            // Consent opt-in flags
            $table->boolean('marketing_opt_in')->default(false);
            $table->boolean('reminders_opt_in')->default(false);
            $table->boolean('headhunt_opt_in')->default(false);

            // Consent timestamps (GDPR/KVKK compliance)
            $table->timestamp('data_processing_consent_at')->nullable();
            $table->timestamp('marketing_consent_at')->nullable();
            $table->timestamp('reminders_consent_at')->nullable();
            $table->timestamp('headhunt_consent_at')->nullable();

            // Blocking
            $table->string('blocked_reason', 255)->nullable();
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();

            // One profile per candidate
            $table->unique('pool_candidate_id');
            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->onDelete('cascade');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_timeline_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('event_type', 50); // applied, interview_started, interview_completed, credential_uploaded, credential_updated, reminder_sent, company_feedback_received, rating_submitted
            $table->string('source', 30); // public_portal, hr_portal, octo_admin, system
            $table->json('payload_json')->nullable();
            $table->timestamp('created_at')->nullable();
            // No updated_at — append-only

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->onDelete('cascade');

            $table->index('pool_candidate_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_timeline_events');
    }
};

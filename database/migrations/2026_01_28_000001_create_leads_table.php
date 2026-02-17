<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Company info
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('company_type', ['single', 'chain', 'franchise'])->nullable();
            $table->string('company_size')->nullable(); // 1-10, 11-50, 51-200, 200+
            $table->string('industry')->nullable();
            $table->string('city')->nullable();

            // Pipeline
            $table->enum('status', ['new', 'contacted', 'demo', 'pilot', 'negotiation', 'won', 'lost'])->default('new');
            $table->string('lost_reason')->nullable();

            // Source
            $table->string('source')->default('website'); // website, referral, linkedin, cold_call, event
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();

            // Assignment
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            // Scoring
            $table->integer('lead_score')->default(0); // 0-100
            $table->boolean('is_hot')->default(false);

            // Dates
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('demo_scheduled_at')->nullable();
            $table->timestamp('demo_completed_at')->nullable();
            $table->timestamp('pilot_started_at')->nullable();
            $table->timestamp('pilot_ended_at')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            // Financial
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->decimal('actual_value', 10, 2)->nullable();

            // Notes
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('assigned_to');
            $table->index('next_follow_up_at');
            $table->index('created_at');
        });

        // Lead activities (notes, calls, meetings)
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->enum('type', ['note', 'call', 'email', 'meeting', 'demo', 'status_change', 'task']);
            $table->string('subject')->nullable();
            $table->text('description')->nullable();

            // For calls/meetings
            $table->string('meeting_link')->nullable(); // Zoom/Meet link
            $table->timestamp('scheduled_at')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->enum('outcome', ['completed', 'no_show', 'rescheduled', 'cancelled'])->nullable();

            // For status changes
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();

            // For tasks
            $table->boolean('is_completed')->default(false);
            $table->timestamp('due_at')->nullable();

            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
            $table->index('type');
        });

        // Sales script checklist
        Schema::create('lead_checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');

            $table->string('stage'); // discovery, demo, pilot, closing
            $table->string('item');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->uuid('completed_by')->nullable();

            $table->timestamps();

            $table->index(['lead_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_checklist_items');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};

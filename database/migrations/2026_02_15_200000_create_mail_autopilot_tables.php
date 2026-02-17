<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Email Threads — group messages into conversations
        Schema::create('crm_email_threads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->nullable();
            $table->string('mailbox', 32); // crew/companies/info
            $table->string('subject', 500);
            $table->timestamp('last_message_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->string('status', 32)->default('open'); // open/snoozed/closed/archived
            $table->char('lang_detected', 2)->nullable(); // en/tr/ru
            $table->string('intent', 64)->nullable();
            $table->string('industry_code', 32)->default('general');
            $table->json('classification')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->cascadeOnDelete();
            $table->index('lead_id');
            $table->index('mailbox');
            $table->index('status');
            $table->index('last_message_at');
            $table->index(['mailbox', 'status']);
        });

        // Sequences — automated follow-up chains
        Schema::create('crm_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 128);
            $table->string('industry_code', 32)->default('general');
            $table->char('language', 2)->default('en');
            $table->json('steps'); // [{delay_days, template_key, channel: 'email'}]
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['industry_code', 'language']);
            $table->index('active');
        });

        // Sequence Enrollments — track lead progress through sequences
        Schema::create('crm_sequence_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->uuid('sequence_id');
            $table->tinyInteger('current_step')->default(0);
            $table->string('status', 32)->default('active'); // active/paused/completed/cancelled
            $table->timestamp('next_step_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->cascadeOnDelete();
            $table->foreign('sequence_id')->references('id')->on('crm_sequences')->cascadeOnDelete();
            $table->unique(['lead_id', 'sequence_id']);
            $table->index('status');
            $table->index('next_step_at');
        });

        // Outbound Queue — all outgoing emails pass through approval queue
        Schema::create('crm_outbound_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->uuid('email_thread_id')->nullable();
            $table->string('from_email', 255);
            $table->string('to_email', 255);
            $table->string('subject', 500);
            $table->text('body_text');
            $table->text('body_html')->nullable();
            $table->string('template_key', 64)->nullable();
            $table->string('source', 32); // draft/sequence/manual/ai_reply
            $table->string('status', 32)->default('draft'); // draft/approved/sending/sent/failed/cancelled
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->cascadeOnDelete();
            $table->foreign('email_thread_id')->references('id')->on('crm_email_threads')->nullOnDelete();
            $table->index('status');
            $table->index('source');
            $table->index('lead_id');
            $table->index('scheduled_at');
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_outbound_queue');
        Schema::dropIfExists('crm_sequence_enrollments');
        Schema::dropIfExists('crm_sequences');
        Schema::dropIfExists('crm_email_threads');
    }
};

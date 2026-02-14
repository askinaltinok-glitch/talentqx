<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_email_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->string('direction', 16); // outbound, inbound
            $table->string('provider', 64)->nullable(); // smtp, imap, gmail_api
            $table->string('message_id', 500)->nullable(); // SMTP Message-ID
            $table->string('thread_id', 500)->nullable();
            $table->string('in_reply_to', 500)->nullable(); // for threading
            $table->string('from_email', 255);
            $table->string('to_email', 255);
            $table->string('subject', 500)->nullable();
            $table->text('body_text')->nullable();
            $table->text('body_html')->nullable();
            $table->json('attachments')->nullable(); // [{file_id, name, size, mime}]
            $table->json('raw_headers')->nullable(); // stored for debugging
            $table->string('status', 32)->default('queued');
            // queued, sent, delivered, bounced, replied
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->onDelete('cascade');
            $table->index('lead_id');
            $table->index('direction');
            $table->index('message_id');
            $table->index('thread_id');
            $table->index('from_email');
            $table->index('to_email');
            $table->index('status');
            $table->index('sent_at');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_email_messages');
    }
};

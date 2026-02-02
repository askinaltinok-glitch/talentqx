<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');

            // Message type: sms, email, whatsapp, push
            $table->string('channel', 20);

            // Recipient info
            $table->string('recipient', 255); // phone number or email
            $table->string('recipient_name')->nullable();

            // Message content
            $table->string('subject')->nullable(); // for email
            $table->text('body');
            $table->json('template_data')->nullable(); // for template variables
            $table->string('template_id', 100)->nullable(); // reference to message_templates

            // Related entities
            $table->string('related_type', 100)->nullable(); // candidate, interview, job_post
            $table->uuid('related_id')->nullable();

            // Status tracking
            $table->string('status', 20)->default('pending'); // pending, processing, sent, failed, cancelled
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('scheduled_at')->nullable(); // for delayed sending
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Error handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();

            // External reference (e.g., SMS provider message ID)
            $table->string('external_id', 255)->nullable();

            // Priority (higher = more urgent)
            $table->integer('priority')->default(0);

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->index('company_id');
            $table->index('status');
            $table->index('channel');
            $table->index('scheduled_at');
            $table->index('priority');
            $table->index(['status', 'scheduled_at', 'priority']); // for worker queries
            $table->index(['related_type', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_outbox');
    }
};

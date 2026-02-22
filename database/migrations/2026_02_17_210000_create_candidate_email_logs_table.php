<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_email_logs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('pool_candidate_id')->nullable();
            $t->uuid('interview_id')->nullable();
            $t->string('mail_type', 64);          // application_received, interview_completed
            $t->string('language', 8)->default('tr');
            $t->string('to_email', 255);
            $t->string('subject', 500);
            $t->string('status', 32)->default('queued'); // queued, sent, failed
            $t->text('smtp_response')->nullable();
            $t->text('error_message')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();

            $t->foreign('pool_candidate_id')
               ->references('id')->on('pool_candidates')
               ->nullOnDelete();

            $t->index(['pool_candidate_id', 'mail_type']);
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_email_logs');
    }
};

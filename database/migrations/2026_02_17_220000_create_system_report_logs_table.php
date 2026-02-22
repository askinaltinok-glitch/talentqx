<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_report_logs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('report_type', 64);        // daily_octopus, weekly_summary, etc.
            $t->date('report_date');                // which day this report covers
            $t->string('to_email', 255);
            $t->string('subject', 500);
            $t->string('status', 32)->default('queued'); // queued, sent, failed
            $t->json('metrics_snapshot')->nullable(); // full metrics JSON for audit
            $t->text('error_message')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();

            $t->index(['report_type', 'report_date']);
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_report_logs');
    }
};

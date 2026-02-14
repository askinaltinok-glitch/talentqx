<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->string('type', 64);
            // note, email_sent, email_reply, call, meeting, task, system
            $table->json('payload')->nullable();
            // {subject, snippet, msg_id, body, status_from, status_to, ...}
            $table->uuid('created_by')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->onDelete('cascade');
            $table->index('lead_id');
            $table->index('type');
            $table->index('occurred_at');
            $table->index(['lead_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};

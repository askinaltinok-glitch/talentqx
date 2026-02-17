<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_mail_triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 128);
            $table->string('trigger_event', 64); // new_company, reply_received, no_reply, deal_stage_changed, lead_created
            $table->json('conditions')->nullable(); // {industry, stage, language, days_stale}
            $table->string('action_type', 32); // enroll_sequence, send_template, generate_ai_reply
            $table->json('action_config'); // {sequence_id, template_key, persona, delay_hours}
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('trigger_event');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_mail_triggers');
    }
};

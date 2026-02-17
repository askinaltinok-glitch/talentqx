<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add thread + classification fields to crm_email_messages
        Schema::table('crm_email_messages', function (Blueprint $table) {
            $table->uuid('email_thread_id')->nullable()->after('lead_id');
            $table->string('mailbox', 32)->nullable()->after('to_email');
            $table->string('from_name', 255)->nullable()->after('from_email');
            $table->char('lang_detected', 2)->nullable()->after('raw_headers');
            $table->string('intent', 64)->nullable()->after('lang_detected');

            $table->foreign('email_thread_id')->references('id')->on('crm_email_threads')->nullOnDelete();
            $table->index('email_thread_id');
        });

        // Add follow-up tracking fields to crm_leads
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->char('preferred_language', 2)->nullable()->after('notes');
            $table->timestamp('last_contacted_at')->nullable()->after('last_activity_at');
            $table->timestamp('next_follow_up_at')->nullable()->after('last_contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('crm_email_messages', function (Blueprint $table) {
            $table->dropForeign(['email_thread_id']);
            $table->dropColumn(['email_thread_id', 'mailbox', 'from_name', 'lang_detected', 'intent']);
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn(['preferred_language', 'last_contacted_at', 'next_follow_up_at']);
        });
    }
};

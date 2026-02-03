<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            // Email tracking fields
            $table->timestamp('invitation_sent_at')->nullable()->after('completed_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('invitation_sent_at');
            $table->timestamp('completion_email_sent_at')->nullable()->after('reminder_sent_at');

            // Interview scheduling (for reminder calculation)
            $table->timestamp('scheduled_at')->nullable()->after('completion_email_sent_at');

            // Index for reminder query
            $table->index(['status', 'reminder_sent_at', 'token_expires_at'], 'interviews_reminder_idx');
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropIndex('interviews_reminder_idx');
            $table->dropColumn([
                'invitation_sent_at',
                'reminder_sent_at',
                'completion_email_sent_at',
                'scheduled_at',
            ]);
        });
    }
};

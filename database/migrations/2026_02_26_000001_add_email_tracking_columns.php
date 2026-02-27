<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'hired_email_sent_at')) {
                $table->timestamp('hired_email_sent_at')->nullable()->after('status_note');
            }
            if (!Schema::hasColumn('candidates', 'rejected_email_sent_at')) {
                $table->timestamp('rejected_email_sent_at')->nullable()->after('hired_email_sent_at');
            }
        });

        Schema::table('interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('interviews', 'company_notified_at')) {
                $table->timestamp('company_notified_at')->nullable()->after('completion_email_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['hired_email_sent_at', 'rejected_email_sent_at']);
        });

        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn('company_notified_at');
        });
    }
};

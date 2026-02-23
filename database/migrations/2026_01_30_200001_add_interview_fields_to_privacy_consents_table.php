<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privacy_consents', function (Blueprint $table) {
            if (!Schema::hasColumn('privacy_consents', 'interview_session_id')) {
                $table->uuid('interview_session_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('privacy_consents', 'recording_accepted')) {
                $table->boolean('recording_accepted')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('privacy_consents', function (Blueprint $table) {
            $table->dropColumn(['interview_session_id', 'recording_accepted']);
        });
    }
};

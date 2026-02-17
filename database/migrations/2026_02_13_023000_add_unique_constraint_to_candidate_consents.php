<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_consents', function (Blueprint $table) {
            // Add unique constraint for idempotent consent recording
            $table->unique(
                ['form_interview_id', 'consent_type'],
                'candidate_consents_interview_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('candidate_consents', function (Blueprint $table) {
            $table->dropUnique('candidate_consents_interview_type_unique');
        });
    }
};

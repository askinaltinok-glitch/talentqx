<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->string('type', 30)->default('standard')->after('status');
            $table->string('command_class_detected', 30)->nullable()->after('decision_summary_json');
            $table->uuid('command_profile_id')->nullable()->after('command_class_detected');
            $table->tinyInteger('interview_phase')->nullable()->after('command_profile_id');
            $table->timestamp('phase1_completed_at')->nullable()->after('interview_phase');

            $table->index('type');
        });

        // Add identity_confidence_score to profiles
        Schema::table('candidate_command_profiles', function (Blueprint $table) {
            $table->decimal('identity_confidence_score', 5, 2)->nullable()->after('confidence_score');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'command_class_detected', 'command_profile_id',
                'interview_phase', 'phase1_completed_at',
            ]);
        });

        Schema::table('candidate_command_profiles', function (Blueprint $table) {
            $table->dropColumn('identity_confidence_score');
        });
    }
};

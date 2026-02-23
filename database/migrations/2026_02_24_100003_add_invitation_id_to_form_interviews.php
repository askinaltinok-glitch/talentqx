<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->uuid('interview_invitation_id')->nullable()->after('pool_candidate_id');
            $table->foreign('interview_invitation_id')->references('id')->on('interview_invitations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropForeign(['interview_invitation_id']);
            $table->dropColumn('interview_invitation_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->uuid('behavioral_profile_id')->nullable()->after('override_by_user_id');
            $table->index('behavioral_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex(['behavioral_profile_id']);
            $table->dropColumn('behavioral_profile_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->boolean('trust_opt_in')->default(false)->after('headhunt_consent_at');
            $table->timestamp('trust_consent_at')->nullable()->after('trust_opt_in');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropColumn(['trust_opt_in', 'trust_consent_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('compliance_score')->nullable()->after('risk_tier');
            $table->string('compliance_status', 20)->nullable()->after('compliance_score');
            $table->timestamp('compliance_computed_at')->nullable()->after('compliance_status');

            $table->index('compliance_status');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->dropIndex(['compliance_status']);
            $table->dropColumn(['compliance_score', 'compliance_status', 'compliance_computed_at']);
        });
    }
};

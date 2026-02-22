<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->decimal('stability_index', 8, 4)->nullable()->after('detail_json');
            $table->decimal('risk_score', 5, 4)->nullable()->after('stability_index');
            $table->string('risk_tier', 16)->nullable()->after('risk_score');
            // low | medium | high | critical
        });
    }

    public function down(): void
    {
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->dropColumn(['stability_index', 'risk_score', 'risk_tier']);
        });
    }
};

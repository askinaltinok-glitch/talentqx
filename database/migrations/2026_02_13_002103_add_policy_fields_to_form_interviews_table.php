<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('form_interviews', 'policy_code')) {
                $table->string('policy_code', 64)->nullable()->after('decision_reason');
            }
            if (!Schema::hasColumn('form_interviews', 'policy_version')) {
                $table->string('policy_version', 16)->default('v1')->after('policy_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn(['policy_code', 'policy_version']);
        });
    }
};

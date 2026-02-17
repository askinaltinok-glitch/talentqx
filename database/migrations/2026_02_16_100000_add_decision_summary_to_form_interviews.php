<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('form_interviews', 'decision_summary_json')) {
            Schema::table('form_interviews', function (Blueprint $table) {
                $table->json('decision_summary_json')->nullable()->after('admin_notes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn('decision_summary_json');
        });
    }
};

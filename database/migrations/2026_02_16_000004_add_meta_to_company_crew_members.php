<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_crew_members', function (Blueprint $table) {
            if (!Schema::hasColumn('company_crew_members', 'meta')) {
                $table->json('meta')->nullable()->after('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_crew_members', function (Blueprint $table) {
            if (Schema::hasColumn('company_crew_members', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};

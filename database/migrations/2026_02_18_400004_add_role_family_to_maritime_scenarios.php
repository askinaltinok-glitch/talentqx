<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maritime_scenarios', function (Blueprint $table) {
            $table->string('role_family', 20)->default('deck')->after('command_class');

            // Composite index for future role-family-based scenario selection
            $table->index(['role_family', 'command_class', 'slot', 'version'], 'idx_scenario_role_class_slot_ver');
        });
    }

    public function down(): void
    {
        Schema::table('maritime_scenarios', function (Blueprint $table) {
            $table->dropIndex('idx_scenario_role_class_slot_ver');
            $table->dropColumn('role_family');
        });
    }
};

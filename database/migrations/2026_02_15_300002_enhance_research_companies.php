<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_companies', function (Blueprint $table) {
            $table->string('fleet_type', 64)->nullable()->after('fleet_size_est');
            $table->unsignedInteger('vessel_count')->nullable()->after('fleet_type');
            $table->unsignedInteger('crew_size_est')->nullable()->after('vessel_count');
            $table->boolean('target_list')->default(false)->after('crew_size_est');

            $table->index('target_list');
        });
    }

    public function down(): void
    {
        Schema::table('research_companies', function (Blueprint $table) {
            $table->dropIndex(['target_list']);
            $table->dropColumn(['fleet_type', 'vessel_count', 'crew_size_est', 'target_list']);
        });
    }
};

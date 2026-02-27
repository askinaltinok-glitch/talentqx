<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->string('company_type', 40)->nullable()->after('country');
            $table->string('fleet_size', 20)->nullable()->after('company_type');
            $table->string('active_crew', 20)->nullable()->after('fleet_size');
            $table->string('monthly_hires', 20)->nullable()->after('active_crew');
            $table->json('vessel_types')->nullable()->after('monthly_hires');
            $table->json('main_ranks')->nullable()->after('vessel_types');
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropColumn(['company_type', 'fleet_size', 'active_crew', 'monthly_hires', 'vessel_types', 'main_ranks']);
        });
    }
};

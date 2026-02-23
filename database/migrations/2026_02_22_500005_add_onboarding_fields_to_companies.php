<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('timezone', 60)->nullable()->after('country');
            $table->string('fleet_size', 30)->nullable()->after('timezone');
            $table->string('management_type', 40)->nullable()->after('fleet_size');
            $table->boolean('onboarding_completed')->default(false)->after('management_type');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'fleet_size', 'management_type', 'onboarding_completed']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->string('mmsi', 20)->nullable()->after('imo')->index();
            $table->string('vessel_type_raw', 100)->nullable()->after('type');
            $table->string('vessel_type_normalized', 50)->nullable()->after('vessel_type_raw');
            $table->string('static_source', 30)->default('manual')->after('data_source');
        });
    }

    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropIndex(['mmsi']);
            $table->dropColumn(['mmsi', 'vessel_type_raw', 'vessel_type_normalized', 'static_source']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->string('platform_code', 32)->nullable()->after('industry_code');
            $table->string('brand_domain', 128)->nullable()->after('platform_code');
            $table->index('platform_code');
        });

        // Backfill: maritime → octopus, everything else → talentqx
        DB::table('form_interviews')
            ->where('industry_code', 'maritime')
            ->whereNull('platform_code')
            ->update(['platform_code' => 'octopus', 'brand_domain' => 'octopus-ai.net']);

        DB::table('form_interviews')
            ->where(function ($q) {
                $q->whereNull('industry_code')
                  ->orWhere('industry_code', '!=', 'maritime');
            })
            ->whereNull('platform_code')
            ->update(['platform_code' => 'talentqx', 'brand_domain' => 'talentqx.com']);
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex(['platform_code']);
            $table->dropColumn(['platform_code', 'brand_domain']);
        });
    }
};

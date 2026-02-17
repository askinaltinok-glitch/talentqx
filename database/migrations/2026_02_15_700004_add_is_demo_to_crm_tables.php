<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // crm_leads
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('notes');
            $table->index('is_demo', 'idx_cl_is_demo');
        });

        DB::statement("UPDATE crm_leads SET is_demo = 1 WHERE source_channel = 'demo'");

        // crm_deals
        Schema::table('crm_deals', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('notes');
            $table->index('is_demo', 'idx_cd_is_demo');
        });

        DB::statement("
            UPDATE crm_deals cd
            JOIN crm_leads cl ON cd.lead_id = cl.id
            SET cd.is_demo = 1
            WHERE cl.source_channel = 'demo'
        ");
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropIndex('idx_cl_is_demo');
            $table->dropColumn('is_demo');
        });
        Schema::table('crm_deals', function (Blueprint $table) {
            $table->dropIndex('idx_cd_is_demo');
            $table->dropColumn('is_demo');
        });
    }
};

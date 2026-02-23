<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->timestamp('logbook_activated_at')->nullable()->after('last_assessed_at');
            $table->timestamp('last_seen_at')->nullable()->after('logbook_activated_at');
            $table->string('availability_status', 32)->default('unknown')->after('last_seen_at');
            $table->timestamp('availability_updated_at')->nullable()->after('availability_status');
            $table->date('contract_end_estimate')->nullable()->after('availability_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'logbook_activated_at',
                'last_seen_at',
                'availability_status',
                'availability_updated_at',
                'contract_end_estimate',
            ]);
        });
    }
};

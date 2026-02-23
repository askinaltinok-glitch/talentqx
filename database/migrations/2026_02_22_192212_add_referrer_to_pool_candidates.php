<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->uuid('referred_by_id')->nullable()->after('source_channel');
            $table->string('referral_code', 20)->nullable()->unique()->after('referred_by_id');
            $table->unsignedInteger('referral_count')->default(0)->after('referral_code');

            $table->foreign('referred_by_id')
                ->references('id')
                ->on('pool_candidates')
                ->nullOnDelete();

            $table->index('referred_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn(['referred_by_id', 'referral_code', 'referral_count']);
        });
    }
};

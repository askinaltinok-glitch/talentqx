<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Monthly credit allocation based on plan
            $table->integer('monthly_credits')->default(5)->after('settings');
            // Credits used in current period
            $table->integer('credits_used')->default(0)->after('monthly_credits');
            // Current billing period start date
            $table->date('credits_period_start')->nullable()->after('credits_used');
            // Bonus credits added by admin
            $table->integer('bonus_credits')->default(0)->after('credits_period_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'monthly_credits',
                'credits_used',
                'credits_period_start',
                'bonus_credits',
            ]);
        });
    }
};

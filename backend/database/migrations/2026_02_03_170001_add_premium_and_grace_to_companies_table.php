<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds premium flag and grace period support for subscription management.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('subscription_ends_at');
            }
            if (!Schema::hasColumn('companies', 'grace_period_ends_at')) {
                $table->timestamp('grace_period_ends_at')->nullable()->after('is_premium');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['is_premium', 'grace_period_ends_at']);
        });
    }
};

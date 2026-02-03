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
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('visibility_scope', 30)->default('customer_only')->after('tags');
            $table->boolean('marketplace_consent')->default(false)->after('visibility_scope');
            $table->timestamp('marketplace_consent_at')->nullable()->after('marketplace_consent');

            // Index for marketplace queries
            $table->index(['visibility_scope', 'marketplace_consent'], 'idx_marketplace_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('idx_marketplace_visibility');
            $table->dropColumn(['visibility_scope', 'marketplace_consent', 'marketplace_consent_at']);
        });
    }
};

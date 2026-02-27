<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pool_candidates', 'is_erased')) {
            return; // idempotent guard
        }

        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->boolean('is_erased')->default(false)->after('is_demo');
            $table->timestamp('erased_at')->nullable()->after('is_erased');
            $table->text('erasure_reason')->nullable()->after('erased_at');

            $table->index('is_erased');
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropIndex(['is_erased']);
            $table->dropColumn(['is_erased', 'erased_at', 'erasure_reason']);
        });
    }
};

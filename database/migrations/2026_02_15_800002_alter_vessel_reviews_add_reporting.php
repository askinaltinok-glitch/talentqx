<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessel_reviews', function (Blueprint $table) {
            $table->unsignedInteger('report_count')->default(0)->after('admin_notes');
            $table->timestamp('published_at')->nullable()->after('report_count');
            $table->softDeletes();

            $table->index('report_count');
            $table->index('deleted_at');
        });

        // Backfill: approved reviews get published_at = created_at
        DB::statement("UPDATE vessel_reviews SET published_at = created_at WHERE status = 'approved'");
    }

    public function down(): void
    {
        Schema::table('vessel_reviews', function (Blueprint $table) {
            $table->dropIndex(['report_count']);
            $table->dropIndex(['deleted_at']);
            $table->dropColumn(['report_count', 'published_at', 'deleted_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profile_views', function (Blueprint $table) {
            $table->string('company_name', 255)->nullable()->after('viewer_name');
            $table->unsignedInteger('view_duration_seconds')->nullable()->after('company_name');

            $table->index('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profile_views', function (Blueprint $table) {
            $table->dropIndex(['company_name']);
            $table->dropColumn(['company_name', 'view_duration_seconds']);
        });
    }
};

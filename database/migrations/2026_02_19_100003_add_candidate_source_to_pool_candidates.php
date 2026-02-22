<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->string('candidate_source', 32)->nullable()->after('source_meta');
            // Values: public_portal, company_upload, octo_admin, import
            $table->index('candidate_source');
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropIndex(['candidate_source']);
            $table->dropColumn('candidate_source');
        });
    }
};

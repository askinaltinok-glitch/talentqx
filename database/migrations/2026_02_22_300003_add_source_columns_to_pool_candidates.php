<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->string('source_type')->default('organic')
                ->after('source_meta')
                ->comment('organic, company_invite, referral, bulk_import');
            $table->uuid('source_company_id')->nullable()->after('source_type');
            $table->string('source_label')->nullable()
                ->after('source_company_id')
                ->comment('Snapshot of company trade name at apply time');

            $table->foreign('source_company_id')->references('id')->on('companies')->nullOnDelete();
            $table->index('source_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropForeign(['source_company_id']);
            $table->dropIndex(['source_company_id']);
            $table->dropColumn(['source_type', 'source_company_id', 'source_label']);
        });
    }
};

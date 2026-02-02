<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('id');
            $table->uuid('branch_id')->nullable()->after('company_id');

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->index('company_id');
            $table->index('branch_id');
        });

        // Populate company_id from job_postings for existing records
        DB::statement('
            UPDATE candidates c
            SET company_id = (SELECT company_id FROM job_postings j WHERE j.id = c.job_id)
            WHERE c.company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['company_id', 'branch_id']);
        });
    }
};

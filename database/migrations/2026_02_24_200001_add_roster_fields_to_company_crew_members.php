<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('company_crew_members', 'passport_no')) {
            return; // Already applied via SQL
        }
        Schema::table('company_crew_members', function (Blueprint $table) {
            $table->string('passport_no', 50)->nullable()->after('language');
            $table->string('seamans_book_no', 50)->nullable()->after('passport_no');
            $table->date('date_of_birth')->nullable()->after('seamans_book_no');
            $table->string('vessel_name', 150)->nullable()->after('date_of_birth');
            $table->string('vessel_country', 5)->nullable()->after('vessel_name');
            $table->date('contract_start_at')->nullable()->after('vessel_country');
            $table->date('contract_end_at')->nullable()->after('contract_start_at');
            $table->string('rank_raw', 100)->nullable()->after('contract_end_at');
            $table->char('import_run_id', 36)->nullable()->after('rank_raw');

            $table->index(['company_id', 'passport_no']);
            $table->index(['company_id', 'seamans_book_no']);
        });
    }

    public function down(): void
    {
        Schema::table('company_crew_members', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'passport_no']);
            $table->dropIndex(['company_id', 'seamans_book_no']);
            $table->dropColumn([
                'passport_no', 'seamans_book_no', 'date_of_birth',
                'vessel_name', 'vessel_country',
                'contract_start_at', 'contract_end_at',
                'rank_raw', 'import_run_id',
            ]);
        });
    }
};

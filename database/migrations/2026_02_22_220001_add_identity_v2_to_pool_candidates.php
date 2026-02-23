<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->char('nationality', 2)->nullable()->after('country_code')
                  ->comment('ISO 3166-1 alpha-2 nationality code');
            $table->char('country_of_residence', 2)->nullable()->after('nationality')
                  ->comment('ISO 3166-1 alpha-2 current residence');
            $table->date('passport_expiry')->nullable()->after('country_of_residence');
            $table->string('visa_status', 32)->nullable()->after('passport_expiry')
                  ->comment('none, valid, expired, pending');
            $table->char('license_country', 2)->nullable()->after('visa_status')
                  ->comment('Country that issued CoC/license');
            $table->string('license_class', 32)->nullable()->after('license_country')
                  ->comment('e.g. master, chief_officer, second_officer');
            $table->string('flag_endorsement', 64)->nullable()->after('license_class')
                  ->comment('Flag state endorsement (comma-separated if multiple)');

            $table->index('nationality', 'idx_pc_nationality');
            $table->index('license_country', 'idx_pc_license_country');
            $table->index('passport_expiry', 'idx_pc_passport_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropIndex('idx_pc_nationality');
            $table->dropIndex('idx_pc_license_country');
            $table->dropIndex('idx_pc_passport_expiry');
            $table->dropColumn([
                'nationality',
                'country_of_residence',
                'passport_expiry',
                'visa_status',
                'license_country',
                'license_class',
                'flag_endorsement',
            ]);
        });
    }
};

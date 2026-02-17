<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardize: NULL position_code -> __generic__, make NOT NULL
     */
    public function up(): void
    {
        // 1) Update NULL position_code to __generic__
        DB::table('interview_templates')
            ->whereNull('position_code')
            ->update(['position_code' => '__generic__']);

        // 2) Update 'generic' position_code to __generic__ (from our seeder)
        DB::table('interview_templates')
            ->where('position_code', 'generic')
            ->update(['position_code' => '__generic__']);

        // 3) Update 'generic_v0' to '__generic___v0' for consistency
        DB::table('interview_templates')
            ->where('position_code', 'generic_v0')
            ->update(['position_code' => '__generic___v0']);

        // 4) Drop old unique index
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->dropUnique('interview_templates_unique');
        });

        // 5) Make position_code NOT NULL
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->string('position_code', 100)->nullable(false)->change();
        });

        // 6) Create new composite unique index: version + language + position_code
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->unique(['version', 'language', 'position_code'], 'itpl_vlp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new unique index
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->dropUnique('itpl_vlp_unique');
        });

        // Make position_code nullable again
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->string('position_code', 100)->nullable()->change();
        });

        // Restore old unique index
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->unique(['position_code', 'language', 'version'], 'interview_templates_unique');
        });

        // Revert __generic__ back to 'generic'
        DB::table('interview_templates')
            ->where('position_code', '__generic__')
            ->update(['position_code' => 'generic']);

        // Revert __generic___v0 back to 'generic_v0'
        DB::table('interview_templates')
            ->where('position_code', '__generic___v0')
            ->update(['position_code' => 'generic_v0']);
    }
};

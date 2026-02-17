<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: industry_code should be nullable (not default 'general')
     * Fix: index should use position_code (not template_position_code)
     */
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            // Drop old index
            $table->dropIndex('fi_baseline_v2_idx');
        });

        Schema::table('form_interviews', function (Blueprint $table) {
            // Change column: remove default, make nullable, increase size
            $table->string('industry_code', 64)->nullable()->change();
        });

        // Update existing 'general' values to NULL
        \DB::table('form_interviews')->where('industry_code', 'general')->update(['industry_code' => null]);

        Schema::table('form_interviews', function (Blueprint $table) {
            // New index with position_code instead of template_position_code
            $table->index(
                ['version', 'language', 'position_code', 'industry_code', 'status', 'completed_at'],
                'fi_dims_completed_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex('fi_dims_completed_idx');
        });

        Schema::table('form_interviews', function (Blueprint $table) {
            $table->string('industry_code', 32)->default('general')->change();

            $table->index(
                ['status', 'template_position_code', 'industry_code', 'language', 'version', 'completed_at'],
                'fi_baseline_v2_idx'
            );
        });
    }
};

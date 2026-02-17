<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds industry_code for vertical-specific calibration.
     * Default 'general' for existing records.
     */
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            // Industry code for vertical-specific calibration
            $table->string('industry_code', 32)
                ->default('general')
                ->after('template_position_code')
                ->comment('Industry vertical: general, maritime, retail, healthcare, etc.');

            // Update baseline index to include industry_code
            $table->index(
                ['status', 'template_position_code', 'industry_code', 'language', 'version', 'completed_at'],
                'fi_baseline_v2_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex('fi_baseline_v2_idx');
            $table->dropColumn('industry_code');
        });
    }
};

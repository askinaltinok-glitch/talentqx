<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            // DecisionEngine raw outputs (keep as-is but store explicitly)
            if (!Schema::hasColumn('form_interviews', 'raw_final_score')) {
                $table->unsignedSmallInteger('raw_final_score')->nullable()->after('final_score');
            }
            if (!Schema::hasColumn('form_interviews', 'raw_decision')) {
                $table->string('raw_decision', 16)->nullable()->after('decision');
            }
            if (!Schema::hasColumn('form_interviews', 'raw_decision_reason')) {
                $table->text('raw_decision_reason')->nullable()->after('decision_reason');
            }

            // Calibration snapshot used at scoring time
            if (!Schema::hasColumn('form_interviews', 'position_mean_score')) {
                $table->decimal('position_mean_score', 6, 2)->nullable()->after('raw_final_score');
            }
            if (!Schema::hasColumn('form_interviews', 'position_std_dev_score')) {
                $table->decimal('position_std_dev_score', 6, 2)->nullable()->after('position_mean_score');
            }
            if (!Schema::hasColumn('form_interviews', 'z_score')) {
                $table->decimal('z_score', 8, 4)->nullable()->after('position_std_dev_score');
            }
            if (!Schema::hasColumn('form_interviews', 'calibrated_score')) {
                $table->unsignedSmallInteger('calibrated_score')->nullable()->after('z_score');
            }
            if (!Schema::hasColumn('form_interviews', 'calibration_version')) {
                $table->string('calibration_version', 16)->default('v1')->after('calibrated_score');
            }

            // Helpful index for baseline queries
            $table->index(['status', 'template_position_code', 'language', 'version'], 'fi_baseline_idx');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex('fi_baseline_idx');
            $table->dropColumn([
                'raw_final_score',
                'raw_decision',
                'raw_decision_reason',
                'position_mean_score',
                'position_std_dev_score',
                'z_score',
                'calibrated_score',
                'calibration_version',
            ]);
        });
    }
};

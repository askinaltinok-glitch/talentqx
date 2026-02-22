<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Capability rubrics: scoring axes per capability
        Schema::create('capability_rubrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('capability_code', 20)->index();
            $table->string('scoring_axis', 40);
            $table->text('level_1_description');
            $table->text('level_2_description');
            $table->text('level_3_description');
            $table->text('level_4_description');
            $table->text('level_5_description');
            $table->decimal('weight_default', 4, 3)->default(0.25);
            $table->timestamps();

            $table->unique(['capability_code', 'scoring_axis']);
        });

        // Capability scores: 7 independent axes per interview
        Schema::create('capability_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id')->index();
            $table->uuid('candidate_id')->index();
            $table->string('command_class', 30);

            // 7 raw scores (0-100)
            $table->decimal('nav_complex_raw', 5, 1)->default(0);
            $table->decimal('cmd_scale_raw', 5, 1)->default(0);
            $table->decimal('tech_depth_raw', 5, 1)->default(0);
            $table->decimal('risk_mgmt_raw', 5, 1)->default(0);
            $table->decimal('crew_lead_raw', 5, 1)->default(0);
            $table->decimal('auto_dep_raw', 5, 1)->default(0);
            $table->decimal('crisis_rsp_raw', 5, 1)->default(0);

            // 7 adjusted scores (after class modifier)
            $table->decimal('nav_complex_adj', 5, 1)->default(0);
            $table->decimal('cmd_scale_adj', 5, 1)->default(0);
            $table->decimal('tech_depth_adj', 5, 1)->default(0);
            $table->decimal('risk_mgmt_adj', 5, 1)->default(0);
            $table->decimal('crew_lead_adj', 5, 1)->default(0);
            $table->decimal('auto_dep_adj', 5, 1)->default(0);
            $table->decimal('crisis_rsp_adj', 5, 1)->default(0);

            // Full axis breakdown
            $table->json('axis_scores');

            // Command Readiness Level
            $table->string('crl', 5)->nullable();
            $table->json('deployment_flags')->nullable();

            // Metadata
            $table->string('scoring_version', 10)->default('v2');
            $table->timestamp('scored_at');
            $table->timestamps();

            $table->foreign('form_interview_id')->references('id')->on('form_interviews');
            $table->foreign('candidate_id')->references('id')->on('candidates');
        });

        // Deployment packet stored on form_interviews
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->json('capability_profile_json')->nullable()->after('decision_summary_json');
            $table->json('deployment_packet_json')->nullable()->after('capability_profile_json');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn(['capability_profile_json', 'deployment_packet_json']);
        });

        Schema::dropIfExists('capability_scores');
        Schema::dropIfExists('capability_rubrics');
    }
};

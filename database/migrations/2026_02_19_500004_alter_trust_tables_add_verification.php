<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── candidate_contracts: add verification + imo + notes + updated_at, rename rank → rank_code ──
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->renameColumn('rank', 'rank_code');
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->string('vessel_imo', 20)->nullable()->after('vessel_name');
            $table->uuid('verified_by_company_id')->nullable()->after('verified');
            $table->uuid('verified_by_user_id')->nullable()->after('verified_by_company_id');
            $table->timestamp('verified_at')->nullable()->after('verified_by_user_id');
            $table->text('notes')->nullable()->after('verified_at');
            $table->timestamp('updated_at')->nullable()->after('created_at');

            $table->index('vessel_imo');
            $table->index('verified_by_company_id');
            $table->index('rank_code');
            $table->index(['pool_candidate_id', 'end_date']);
        });

        // ── candidate_trust_profiles: add unique_company_count_3y + flags_json ──
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->unsignedInteger('unique_company_count_3y')->default(0)->after('gap_months_total');
            $table->json('flags_json')->nullable()->after('timeline_inconsistency_flag');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_trust_profiles', function (Blueprint $table) {
            $table->dropColumn(['unique_company_count_3y', 'flags_json']);
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropIndex(['vessel_imo']);
            $table->dropIndex(['verified_by_company_id']);
            $table->dropIndex(['rank_code']);
            $table->dropIndex(['pool_candidate_id', 'end_date']);
            $table->dropColumn([
                'vessel_imo', 'verified_by_company_id', 'verified_by_user_id',
                'verified_at', 'notes', 'updated_at',
            ]);
        });

        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->renameColumn('rank_code', 'rank');
        });
    }
};

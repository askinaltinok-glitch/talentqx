<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            // Link to pool candidate (Candidate Supply Engine)
            // Nullable for legacy interviews
            $table->uuid('pool_candidate_id')->nullable()->after('id');

            // Snapshot acquisition data at interview time
            $table->string('acquisition_source_snapshot', 64)->nullable()->after('pool_candidate_id');
            $table->json('acquisition_campaign_snapshot')->nullable()->after('acquisition_source_snapshot');

            // Foreign key constraint
            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->onDelete('set null');

            // Index for lookups
            $table->index('pool_candidate_id');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropForeign(['pool_candidate_id']);
            $table->dropColumn([
                'pool_candidate_id',
                'acquisition_source_snapshot',
                'acquisition_campaign_snapshot',
            ]);
        });
    }
};

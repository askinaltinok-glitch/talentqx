<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill grace_credits_total=5, grace_credits_used=0 for all companies
        // that have NULL settings or settings without these keys.
        DB::statement("
            UPDATE companies
            SET settings = JSON_SET(
                COALESCE(settings, '{}'),
                '$.grace_credits_total', 5,
                '$.grace_credits_used', 0
            )
            WHERE settings IS NULL
               OR JSON_EXTRACT(settings, '$.grace_credits_total') IS NULL
        ");
    }

    public function down(): void
    {
        // Remove grace keys from settings JSON
        DB::statement("
            UPDATE companies
            SET settings = JSON_REMOVE(settings, '$.grace_credits_total', '$.grace_credits_used')
            WHERE settings IS NOT NULL
              AND JSON_EXTRACT(settings, '$.grace_credits_total') IS NOT NULL
        ");
    }
};

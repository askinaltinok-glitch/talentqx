<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL JSON — backfill grace_days=5 for companies missing it
        DB::statement("
            UPDATE companies
            SET settings = JSON_SET(
                COALESCE(settings, '{}'),
                '$.grace_days', 5
            )
            WHERE settings IS NULL
               OR JSON_EXTRACT(settings, '$.grace_days') IS NULL
        ");

        // MySQL JSON — backfill show_behavioral_details=false for companies missing it
        DB::statement("
            UPDATE companies
            SET settings = JSON_SET(
                COALESCE(settings, '{}'),
                '$.show_behavioral_details', false
            )
            WHERE settings IS NULL
               OR JSON_EXTRACT(settings, '$.show_behavioral_details') IS NULL
        ");
    }

    public function down(): void
    {
        // No rollback — these are safe defaults
    }
};

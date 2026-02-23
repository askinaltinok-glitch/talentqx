<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->after('status');
            $table->index('public_token');
        });

        // Backfill existing candidates with tokens
        \Illuminate\Support\Facades\DB::table('pool_candidates')
            ->whereNull('public_token')
            ->orderBy('id')
            ->chunk(500, function ($candidates) {
                foreach ($candidates as $candidate) {
                    \Illuminate\Support\Facades\DB::table('pool_candidates')
                        ->where('id', $candidate->id)
                        ->update(['public_token' => bin2hex(random_bytes(32))]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropIndex(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->string('public_token_hash', 64)->nullable()->after('public_token');
            $table->index('public_token_hash');
        });

        // Backfill: hash existing plaintext tokens
        \Illuminate\Support\Facades\DB::table('pool_candidates')
            ->whereNotNull('public_token')
            ->whereNull('public_token_hash')
            ->orderBy('id')
            ->chunk(500, function ($candidates) {
                foreach ($candidates as $candidate) {
                    \Illuminate\Support\Facades\DB::table('pool_candidates')
                        ->where('id', $candidate->id)
                        ->update(['public_token_hash' => hash('sha256', $candidate->public_token)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropIndex(['public_token_hash']);
            $table->dropColumn('public_token_hash');
        });
    }
};

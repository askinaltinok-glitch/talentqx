<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('public_hash', 32)->nullable()->unique()->after('id');
        });

        // Generate public_hash for existing candidates
        DB::table('candidates')->whereNull('public_hash')->orderBy('id')->chunk(100, function ($candidates) {
            foreach ($candidates as $candidate) {
                DB::table('candidates')
                    ->where('id', $candidate->id)
                    ->update(['public_hash' => Str::random(32)]);
            }
        });

        // Make it non-nullable after populating
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('public_hash', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('public_hash');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maritime_roles', function (Blueprint $table) {
            $table->boolean('is_selectable')->default(true)->after('is_active');
        });

        // Backfill: all existing rows get is_selectable = true
        \Illuminate\Support\Facades\DB::table('maritime_roles')
            ->whereNull('is_selectable')
            ->update(['is_selectable' => true]);
    }

    public function down(): void
    {
        Schema::table('maritime_roles', function (Blueprint $table) {
            $table->dropColumn('is_selectable');
        });
    }
};

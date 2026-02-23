<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_octopus_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_octopus_admin')->default(false)->after('is_platform_admin');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_octopus_admin');
        });
    }
};

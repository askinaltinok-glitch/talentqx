<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maritime_jobs', function (Blueprint $table) {
            $table->string('operation_type', 16)->nullable()->after('is_active');
            $table->index(['is_active', 'operation_type']);
        });
    }

    public function down(): void
    {
        Schema::table('maritime_jobs', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'operation_type']);
            $table->dropColumn('operation_type');
        });
    }
};

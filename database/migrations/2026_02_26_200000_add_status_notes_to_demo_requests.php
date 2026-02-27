<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->string('status', 30)->default('new')->after('user_agent');
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropColumn(['status', 'notes']);
        });
    }
};

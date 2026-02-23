<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seafarer_certificates', function (Blueprint $table) {
            $table->string('expiry_source', 24)->default('unknown')->after('expires_at');
            $table->boolean('self_declared')->default(false)->after('expiry_source');
            $table->index('expiry_source');
        });
    }

    public function down(): void
    {
        Schema::table('seafarer_certificates', function (Blueprint $table) {
            $table->dropIndex(['expiry_source']);
            $table->dropColumn(['expiry_source', 'self_declared']);
        });
    }
};

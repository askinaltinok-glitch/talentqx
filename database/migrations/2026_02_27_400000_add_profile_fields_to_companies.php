<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('trade_name', 255)->nullable()->after('name');
            $table->string('phone', 30)->nullable()->after('billing_phone');
            $table->string('website', 255)->nullable()->after('phone');
            $table->string('email', 255)->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['trade_name', 'phone', 'website', 'email']);
        });
    }
};

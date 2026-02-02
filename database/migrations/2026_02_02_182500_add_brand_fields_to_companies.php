<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('brand_email_reply_to', 255)->nullable()->after('logo_url');
            $table->string('brand_primary_color', 7)->nullable()->after('brand_email_reply_to');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['brand_email_reply_to', 'brand_primary_color']);
        });
    }
};

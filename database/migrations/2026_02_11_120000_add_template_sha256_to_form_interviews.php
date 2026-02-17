<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            // SHA256 checksum for template audit/dedup
            $table->string('template_json_sha256', 64)->nullable()->after('template_json');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn('template_json_sha256');
        });
    }
};

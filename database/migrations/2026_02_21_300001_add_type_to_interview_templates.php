<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->string('type', 20)->default('standard')->after('id');
            $table->index(['type', 'language', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('interview_templates', function (Blueprint $table) {
            $table->dropIndex(['type', 'language', 'version']);
            $table->dropColumn('type');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_assessments', function (Blueprint $table) {
            $table->unsignedTinyInteger('retake_count')->default(0)->after('selected_questions');
            $table->timestamp('last_test_at')->nullable()->after('retake_count');
        });
    }

    public function down(): void
    {
        Schema::table('language_assessments', function (Blueprint $table) {
            $table->dropColumn(['retake_count', 'last_test_at']);
        });
    }
};

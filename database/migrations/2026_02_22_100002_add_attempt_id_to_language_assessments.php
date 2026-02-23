<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_assessments', function (Blueprint $table) {
            $table->string('attempt_id', 64)->nullable()->after('candidate_id');
            $table->timestamp('attempt_started_at')->nullable()->after('attempt_id');
            $table->index('attempt_id');
        });
    }

    public function down(): void
    {
        Schema::table('language_assessments', function (Blueprint $table) {
            $table->dropIndex(['attempt_id']);
            $table->dropColumn(['attempt_id', 'attempt_started_at']);
        });
    }
};

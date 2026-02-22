<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_command_profiles', function (Blueprint $table) {
            // Drop FK to candidates — command profiles can reference pool_candidates too
            $table->dropForeign(['candidate_id']);

            // Add derived profile support columns
            $table->string('source', 20)->default('detection')->after('multi_class_flags');
            $table->unsignedTinyInteger('completeness_pct')->default(0)->after('source');
            $table->dateTime('generated_at')->nullable()->after('completeness_pct');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_command_profiles', function (Blueprint $table) {
            $table->dropColumn(['source', 'completeness_pct', 'generated_at']);
            $table->foreign('candidate_id')->references('id')->on('candidates');
        });
    }
};

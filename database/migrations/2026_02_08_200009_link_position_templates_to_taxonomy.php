<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Link position_templates to the new taxonomy
        Schema::table('position_templates', function (Blueprint $table) {
            $table->uuid('job_position_id')->nullable()->after('id');
            $table->foreign('job_position_id')->references('id')->on('job_positions')->onDelete('set null');
            $table->index('job_position_id');
        });

        // Link job_postings to the new taxonomy
        Schema::table('job_postings', function (Blueprint $table) {
            $table->uuid('job_position_id')->nullable()->after('template_id');
            $table->foreign('job_position_id')->references('id')->on('job_positions')->onDelete('set null');
            $table->index('job_position_id');
        });
    }

    public function down(): void
    {
        Schema::table('position_templates', function (Blueprint $table) {
            $table->dropForeign(['job_position_id']);
            $table->dropColumn('job_position_id');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropForeign(['job_position_id']);
            $table->dropColumn('job_position_id');
        });
    }
};

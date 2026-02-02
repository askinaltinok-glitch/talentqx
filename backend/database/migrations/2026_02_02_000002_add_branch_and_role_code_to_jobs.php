<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->uuid('branch_id')->nullable()->after('company_id');
            $table->string('role_code', 20)->nullable()->after('slug');
            $table->string('qr_file_path', 500)->nullable()->after('interview_settings');
            $table->string('apply_url', 500)->nullable()->after('qr_file_path');

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->index('branch_id');
            $table->index('role_code');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['branch_id', 'role_code', 'qr_file_path', 'apply_url']);
        });
    }
};

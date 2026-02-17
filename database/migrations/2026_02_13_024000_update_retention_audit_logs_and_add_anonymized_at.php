<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update retention_audit_logs to match spec
        Schema::table('retention_audit_logs', function (Blueprint $table) {
            // Add batch_size
            $table->integer('batch_size')->default(1000)->after('dry_run');

            // Rename columns to match spec
            $table->renameColumn('interviews_deleted', 'deleted_incomplete_count');
            $table->renameColumn('interviews_anonymized', 'anonymized_completed_count');
            $table->renameColumn('consents_deleted', 'deleted_orphan_consents_count');
            $table->renameColumn('duration_seconds', 'duration_ms');

            // Drop answers_deleted (not in spec)
            $table->dropColumn('answers_deleted');

            // Drop error_details JSON (not in spec)
            $table->dropColumn('error_details');

            // Drop triggered_by (not in spec)
            $table->dropColumn('triggered_by');

            // Add notes
            $table->text('notes')->nullable()->after('duration_ms');
        });

        // Add anonymized_at to form_interviews
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->timestamp('anonymized_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('retention_audit_logs', function (Blueprint $table) {
            $table->dropColumn('batch_size');
            $table->dropColumn('notes');

            $table->renameColumn('deleted_incomplete_count', 'interviews_deleted');
            $table->renameColumn('anonymized_completed_count', 'interviews_anonymized');
            $table->renameColumn('deleted_orphan_consents_count', 'consents_deleted');
            $table->renameColumn('duration_ms', 'duration_seconds');

            $table->integer('answers_deleted')->default(0);
            $table->json('error_details')->nullable();
            $table->string('triggered_by', 64)->default('scheduler');
        });

        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn('anonymized_at');
        });
    }
};

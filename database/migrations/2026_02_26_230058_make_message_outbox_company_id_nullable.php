<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make company_id nullable for system-level messages (e.g. password reset for platform admins).
     */
    public function up(): void
    {
        // Run on both databases
        foreach (['mysql_talentqx', 'mysql'] as $conn) {
            try {
                DB::connection($conn)->statement(
                    'ALTER TABLE message_outbox MODIFY company_id CHAR(36) NULL'
                );
            } catch (\Exception $e) {
                // Table may not exist on one connection
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['mysql_talentqx', 'mysql'] as $conn) {
            try {
                DB::connection($conn)->statement(
                    "UPDATE message_outbox SET company_id = '' WHERE company_id IS NULL"
                );
                DB::connection($conn)->statement(
                    'ALTER TABLE message_outbox MODIFY company_id CHAR(36) NOT NULL'
                );
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
};

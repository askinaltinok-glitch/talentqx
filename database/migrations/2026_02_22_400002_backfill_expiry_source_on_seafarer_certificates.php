<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rows with expires_at set → uploaded by candidate
        DB::table('seafarer_certificates')
            ->whereNotNull('expires_at')
            ->where('expiry_source', 'unknown')
            ->update(['expiry_source' => 'uploaded']);
    }

    public function down(): void
    {
        DB::table('seafarer_certificates')
            ->where('expiry_source', 'uploaded')
            ->update(['expiry_source' => 'unknown']);
    }
};

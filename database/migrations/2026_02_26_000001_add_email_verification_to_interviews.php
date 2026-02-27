<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('interviews', 'email_verification_code_hash')) {
                $table->string('email_verification_code_hash', 64)->nullable()->after('scheduled_at');
            }
            if (!Schema::hasColumn('interviews', 'email_verification_expires_at')) {
                $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code_hash');
            }
            if (!Schema::hasColumn('interviews', 'email_verification_attempts')) {
                $table->tinyInteger('email_verification_attempts')->default(0)->after('email_verification_expires_at');
            }
            if (!Schema::hasColumn('interviews', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email_verification_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $columns = ['email_verification_code_hash', 'email_verification_expires_at', 'email_verification_attempts', 'email_verified_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('interviews', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

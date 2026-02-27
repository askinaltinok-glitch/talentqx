<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('pool_candidates', 'email_verification_otp_hash')) {
                $table->string('email_verification_otp_hash', 64)->nullable()->after('email_verification_token_hash');
            }
            if (!Schema::hasColumn('pool_candidates', 'email_verification_otp_expires_at')) {
                $table->timestamp('email_verification_otp_expires_at')->nullable()->after('email_verification_otp_hash');
            }
            if (!Schema::hasColumn('pool_candidates', 'email_verification_otp_attempts')) {
                $table->tinyInteger('email_verification_otp_attempts')->unsigned()->default(0)->after('email_verification_otp_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pool_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_otp_hash',
                'email_verification_otp_expires_at',
                'email_verification_otp_attempts',
            ]);
        });
    }
};

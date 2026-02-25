<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_push_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('admin_user_id', 36);
            $table->text('endpoint');
            $table->string('endpoint_hash', 64); // SHA-256 of endpoint for unique constraint
            $table->string('public_key', 255);   // p256dh
            $table->string('auth_token', 255);
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->foreign('admin_user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->unique(['admin_user_id', 'endpoint_hash'], 'admin_push_sub_user_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_push_subscriptions');
    }
};

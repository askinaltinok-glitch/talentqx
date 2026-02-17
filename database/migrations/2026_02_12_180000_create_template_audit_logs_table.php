<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_user_id');
            $table->string('admin_email', 100);
            $table->enum('action', ['create', 'update', 'delete', 'activate', 'deactivate', 'clone']);
            $table->uuid('template_id');
            $table->string('template_title', 200)->nullable();
            $table->string('template_version', 10)->nullable();
            $table->string('template_language', 5)->nullable();
            $table->string('template_position_code', 100)->nullable();
            $table->string('before_sha', 64)->nullable(); // SHA256 hex = 64 chars
            $table->string('after_sha', 64)->nullable();
            $table->json('changes')->nullable(); // Field-level changes summary
            $table->string('ip_address', 45)->nullable(); // IPv6 max length
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('admin_user_id');
            $table->index('template_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_audit_logs');
    }
};

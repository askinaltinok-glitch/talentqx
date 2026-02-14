<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('action', 128); // e.g. lead.created, company.updated, email.sent
            $table->string('entity_type', 64); // company, contact, lead, email, task
            $table->uuid('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('action');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_audit_logs');
    }
};

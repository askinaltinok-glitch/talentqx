<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_panel_appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sales_rep_id');
            $table->uuid('lead_id')->nullable();
            $table->string('title');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('customer_timezone', 50)->default('Europe/Istanbul');
            $table->string('status', 20)->default('scheduled')->comment('scheduled, completed, cancelled');
            $table->text('notes')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();

            $table->foreign('sales_rep_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('crm_leads')->nullOnDelete();
            $table->index(['sales_rep_id', 'starts_at']);
            $table->index(['starts_at', 'reminder_sent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_panel_appointments');
    }
};

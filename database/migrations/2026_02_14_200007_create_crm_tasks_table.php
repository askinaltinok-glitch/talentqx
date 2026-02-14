<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->string('type', 64); // follow_up, call, meeting_prep
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 32)->default('open'); // open, done, cancelled
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->onDelete('cascade');
            $table->index('lead_id');
            $table->index('status');
            $table->index('due_at');
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};

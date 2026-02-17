<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('template_id');
            $table->uuid('initiated_by')->nullable();
            $table->string('access_token', 64)->unique();
            $table->timestamp('token_expires_at');
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed, expired, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->json('responses')->nullable(); // [{question_order, answer, time_spent}]
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('device_info')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('assessment_templates');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');

            $table->index('employee_id');
            $table->index('template_id');
            $table->index('status');
            $table->index('access_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_sessions');
    }
};

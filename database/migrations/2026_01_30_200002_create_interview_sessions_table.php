<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_sessions')) {
            Schema::create('interview_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('candidate_id')->nullable();
                $table->string('role_key');
                $table->string('context_key')->nullable();
                $table->string('locale', 5)->default('tr');
                $table->string('status')->default('pending');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['role_key', 'context_key']);
                $table->index('status');
                $table->index('candidate_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_sessions');
    }
};

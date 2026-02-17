<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->string('consent_type', 50);
            $table->string('consent_version', 20);
            $table->text('consent_text');
            $table->string('action', 20);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');

            $table->index('candidate_id');
            $table->index('consent_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};

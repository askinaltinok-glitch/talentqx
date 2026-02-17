<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('agent_name', 64);
            $table->string('status', 32)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('companies_found')->default(0);
            $table->unsignedInteger('signals_detected')->default(0);
            $table->unsignedInteger('leads_created')->default(0);
            $table->json('meta')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();

            $table->index('agent_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_runs');
    }
};

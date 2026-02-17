<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('domain')->nullable()->unique();
            $table->char('country', 2)->nullable();
            $table->string('industry', 32)->default('general');
            $table->string('sub_industry', 64)->nullable();
            $table->boolean('maritime_flag')->default(false);
            $table->unsignedTinyInteger('hiring_signal_score')->default(0);
            $table->string('source', 32)->default('manual');
            $table->json('source_meta')->nullable();
            $table->string('website')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('employee_count_est')->nullable();
            $table->unsignedInteger('fleet_size_est')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('enriched_at')->nullable();
            $table->string('status', 32)->default('discovered');
            $table->json('classification')->nullable();
            $table->timestamps();

            $table->index('industry');
            $table->index('status');
            $table->index('maritime_flag');
            $table->index('hiring_signal_score');
            $table->index(['industry', 'status']);
            $table->index(['maritime_flag', 'hiring_signal_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_companies');
    }
};

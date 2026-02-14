<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('industry_code', 32)->default('general');
            $table->string('query', 500); // "Find maritime crew management companies in India"
            $table->string('status', 32)->default('pending');
            // pending, running, completed, failed
            $table->integer('result_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('meta')->nullable(); // {country_filter, segment_filter, keywords, provider, ...}
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index('industry_code');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_jobs');
    }
};

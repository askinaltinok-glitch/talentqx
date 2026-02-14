<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_company_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->string('name', 255);
            $table->string('domain', 255)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('company_type', 64)->nullable();
            $table->decimal('confidence', 5, 2)->default(0.50); // 0.00–1.00
            $table->json('raw')->nullable(); // full raw scraped data
            $table->json('contact_hints')->nullable(); // [{name, email, title}]
            $table->string('status', 32)->default('pending');
            // pending, accepted, rejected, dismissed
            $table->uuid('imported_company_id')->nullable(); // crm_companies.id after accept
            $table->uuid('reviewed_by')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('research_jobs')->onDelete('cascade');
            $table->index('job_id');
            $table->index('domain');
            $table->index('status');
            $table->index('confidence');
            $table->index(['job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_company_candidates');
    }
};

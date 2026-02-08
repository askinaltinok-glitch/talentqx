<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sub-domains under each job area (e.g., Retail -> Store Management, Cashier, etc.)
        Schema::create('job_subdomains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('domain_id');
            $table->string('code', 100)->unique(); // e.g., 'RETAIL_STORE_MGMT'
            $table->string('name_tr');
            $table->string('name_en');
            $table->string('icon', 50)->nullable();
            $table->text('description_tr')->nullable();
            $table->text('description_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('domain_id')->references('id')->on('job_domains')->onDelete('cascade');
            $table->index('domain_id');
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_subdomains');
    }
};

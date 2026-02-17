<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company Consumption Layer - Companies
        // Represents client companies that consume talent from the pool
        Schema::create('pool_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Company info
            $table->string('company_name', 255);
            $table->string('industry', 64)->default('general');
            $table->char('country', 2)->default('TR');
            $table->enum('size', ['small', 'medium', 'enterprise'])->default('small');

            // Contact info
            $table->string('contact_person', 255)->nullable();
            $table->string('contact_email', 255);
            $table->string('contact_phone', 32)->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('industry');
            $table->index('status');
            $table->unique('contact_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_companies');
    }
};

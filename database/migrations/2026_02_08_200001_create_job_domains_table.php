<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main job areas (e.g., Retail, IT, Healthcare, Finance, etc.)
        Schema::create('job_domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique(); // e.g., 'RETAIL', 'IT', 'HEALTHCARE'
            $table->string('name_tr'); // Turkish name
            $table->string('name_en'); // English name
            $table->string('icon', 50)->nullable(); // Emoji or icon code
            $table->text('description_tr')->nullable();
            $table->text('description_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('sort_order');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_domains');
    }
};

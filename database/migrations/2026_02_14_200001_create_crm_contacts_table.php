<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('full_name', 255);
            $table->string('title', 255)->nullable(); // job title
            $table->string('email', 255)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('preferred_language', 8)->default('en'); // tr, en, ru
            $table->boolean('consent_marketing')->default(false);
            $table->json('consent_meta')->nullable(); // {type, date, ip, ...}
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('crm_companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
};

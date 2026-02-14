<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('storage_disk', 32)->default('local'); // local, s3
            $table->string('path', 1000);
            $table->string('original_name', 500);
            $table->string('mime', 128)->nullable();
            $table->bigInteger('size')->default(0); // bytes
            $table->string('sha256', 64)->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('crm_companies')->onDelete('set null');
            $table->index('lead_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_files');
    }
};

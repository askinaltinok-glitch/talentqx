<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 64); // intro, followup, proposal, re_engagement
            $table->string('industry_code', 32)->default('general');
            $table->string('language', 8)->default('en');
            $table->string('subject', 500);
            $table->text('body_html');
            $table->text('body_text');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'industry_code', 'language']);
            $table->index('active');
            $table->index('industry_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_email_templates');
    }
};

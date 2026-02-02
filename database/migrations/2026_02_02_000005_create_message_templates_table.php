<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable(); // null = system template

            $table->string('code', 100); // unique identifier: interview_invitation, application_received, etc.
            $table->string('name');
            $table->string('channel', 20); // sms, email, whatsapp
            $table->string('locale', 10)->default('tr');

            // Template content
            $table->string('subject')->nullable(); // for email
            $table->text('body');

            // Variables that can be used: {{candidate_name}}, {{job_title}}, etc.
            $table->json('available_variables')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // system templates can't be deleted

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->unique(['company_id', 'code', 'channel', 'locale']);
            $table->index('code');
            $table->index('channel');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};

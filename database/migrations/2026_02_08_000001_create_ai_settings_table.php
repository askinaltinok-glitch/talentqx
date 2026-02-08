<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * AI Settings table stores platform-wide and company-specific AI configuration.
     * API keys are encrypted at rest using Laravel's encryption.
     */
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Null company_id means platform-wide default settings
            $table->uuid('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Provider selection
            $table->string('provider', 50)->default('openai'); // openai, kimi

            // OpenAI settings (encrypted)
            $table->text('openai_api_key')->nullable(); // Encrypted
            $table->string('openai_model', 100)->default('gpt-4o-mini');
            $table->string('openai_whisper_model', 100)->default('whisper-1');

            // Kimi/Moonshot settings (encrypted)
            $table->text('kimi_api_key')->nullable(); // Encrypted
            $table->string('kimi_base_url', 255)->default('https://api.moonshot.ai/v1');
            $table->string('kimi_model', 100)->default('moonshot-v1-128k');

            // Common settings
            $table->integer('timeout')->default(120);
            $table->boolean('is_active')->default(true);

            // Audit
            $table->uuid('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();

            // Ensure only one settings record per company (or one platform-wide)
            $table->unique('company_id', 'ai_settings_company_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_transcriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable()->index();
            $table->uuid('interview_id')->index();
            $table->uuid('candidate_id')->index();
            $table->string('question_id', 50);
            $table->unsignedTinyInteger('slot');
            // Audio metadata
            $table->string('audio_path');
            $table->string('audio_mime', 50);
            $table->unsignedBigInteger('audio_size_bytes');
            $table->char('audio_sha256', 64);
            $table->unsignedInteger('duration_ms')->nullable();
            // Provider
            $table->string('provider', 50)->default('ai_models_panel');
            $table->string('model', 80)->nullable();
            $table->string('language', 10)->default('en');
            // Result
            $table->string('status', 20)->default('pending')->index();
            $table->text('transcript_text')->nullable();
            $table->float('confidence')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Prevent duplicate voice answers per interview+slot
            $table->unique(['interview_id', 'slot'], 'voice_tx_interview_slot_unique');
            // Lookup by status + age (for retry/monitoring)
            $table->index(['status', 'created_at'], 'voice_tx_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_transcriptions');
    }
};

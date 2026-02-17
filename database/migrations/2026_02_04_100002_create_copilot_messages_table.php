<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copilot_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('role', 20); // user, assistant, system
            $table->text('content');
            $table->json('context_snapshot')->nullable(); // What data was used
            $table->json('metadata')->nullable(); // tokens, latency, model
            $table->boolean('guardrail_triggered')->default(false);
            $table->string('guardrail_reason')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('copilot_conversations')
                ->cascadeOnDelete();

            $table->index('conversation_id');
            $table->index('role');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copilot_messages');
    }
};

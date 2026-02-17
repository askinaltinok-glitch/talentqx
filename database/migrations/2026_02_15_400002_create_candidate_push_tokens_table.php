<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_push_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('device_type', 32); // ios, android, web
            $table->text('token');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->cascadeOnDelete();

            $table->index('pool_candidate_id');
        });

        // Add unique constraint via raw SQL since text columns can't have unique index directly
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE candidate_push_tokens ADD UNIQUE INDEX uq_candidate_token (pool_candidate_id, token(255))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_push_tokens');
    }
};

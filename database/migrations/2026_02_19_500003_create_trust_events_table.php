<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trust_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('event_type', 30);
            $table->json('payload_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->cascadeOnDelete();
            $table->index('pool_candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_events');
    }
};

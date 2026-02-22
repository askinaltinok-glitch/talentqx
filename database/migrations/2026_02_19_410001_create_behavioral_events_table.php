<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavioral_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->uuid('interview_id')->index();
            $table->string('event_type', 40); // answer_submitted|finalized|flagged|override
            $table->json('payload_json'); // input snapshot + deltas
            $table->timestamp('created_at');
            // no updated_at — append-only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavioral_events');
    }
};

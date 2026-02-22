<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavioral_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->string('signal_type', 50); // certificate_expiry|response_latency|rating_outlier|etc
            $table->json('signal_value');
            $table->timestamp('observed_at');
            $table->timestamp('created_at');
            // Phase-3 skeleton — no logic yet
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavioral_signals');
    }
};

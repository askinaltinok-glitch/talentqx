<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_detection_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->json('profile_snapshot');
            $table->json('scoring_output');
            $table->string('detected_class', 30)->index();
            $table->decimal('confidence', 5, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('candidate_id')->references('id')->on('candidates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_detection_logs');
    }
};

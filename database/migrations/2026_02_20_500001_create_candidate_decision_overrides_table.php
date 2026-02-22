<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_decision_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->string('decision', 20);   // approve|review|reject
            $table->text('reason');
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('expires_at')->nullable();

            $table->index('candidate_id');
            $table->index(['candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_decision_overrides');
    }
};

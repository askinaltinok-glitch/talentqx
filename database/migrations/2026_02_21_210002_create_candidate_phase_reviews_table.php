<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_phase_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->string('phase_key', 50);
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'approved', 'rejected'])->default('not_started');
            $table->text('review_notes')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'phase_key'], 'cpr_candidate_phase_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_phase_reviews');
    }
};

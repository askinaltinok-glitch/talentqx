<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('tier', 16)->default('free'); // free, plus, pro, enterprise
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')
                ->references('id')
                ->on('pool_candidates')
                ->cascadeOnDelete();

            $table->unique('pool_candidate_id');
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_memberships');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_contact_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('type', 10); // email, phone
            $table->string('value', 190);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Global uniqueness per type+value (force merge/resolve later)
            $table->unique(['type', 'value']);

            $table->foreign('pool_candidate_id')
                ->references('id')->on('pool_candidates')
                ->onDelete('cascade');

            $table->index('pool_candidate_id');
            $table->index('is_primary');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_contact_points');
    }
};

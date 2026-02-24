<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_fit_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id')->index();
            $table->uuid('form_interview_id')->nullable()->index();
            $table->string('applied_role_key', 50);
            $table->string('inferred_role_key', 50)->nullable();
            $table->decimal('role_fit_score', 5, 4);
            $table->string('mismatch_level', 10)->default('none');
            $table->json('mismatch_flags')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('applied_role_key');
            $table->index('mismatch_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_fit_evaluations');
    }
};

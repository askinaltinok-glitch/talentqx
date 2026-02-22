<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_contract_id');
            $table->uuid('vessel_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->text('failure_reason')->nullable();
            $table->uuid('triggered_by_user_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('candidate_contract_id')
                ->references('id')->on('candidate_contracts')
                ->cascadeOnDelete();
            $table->foreign('vessel_id')
                ->references('id')->on('vessels')
                ->nullOnDelete();

            $table->unique('candidate_contract_id');
            $table->index('status');
            $table->index('vessel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_verifications');
    }
};

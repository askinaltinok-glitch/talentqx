<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_ais_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_contract_id');
            $table->uuid('vessel_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('reasons_json')->nullable();
            $table->json('anomalies_json')->nullable();
            $table->json('evidence_summary_json')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('provider', 30)->nullable();
            $table->string('provider_request_id', 100)->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->string('triggered_by', 20)->default('system');
            $table->uuid('triggered_by_user_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('candidate_contract_id')
                ->references('id')->on('candidate_contracts')
                ->cascadeOnDelete();
            $table->foreign('vessel_id')
                ->references('id')->on('vessels')
                ->nullOnDelete();

            $table->index(['candidate_contract_id', 'created_at'], 'cav_contract_created_idx');
            $table->index('status');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_ais_verifications');
    }
};

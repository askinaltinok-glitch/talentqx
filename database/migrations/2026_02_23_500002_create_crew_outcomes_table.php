<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('vessel_id')->index();
            $table->uuid('contract_id')->nullable()->index();
            $table->uuid('captain_candidate_id')->nullable()->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('outcome_type', 30);
            $table->unsignedTinyInteger('severity')->default(0);
            $table->text('notes')->nullable();
            $table->uuid('created_by_user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'vessel_id', 'created_at']);
            $table->index(['captain_candidate_id', 'outcome_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_outcomes');
    }
};

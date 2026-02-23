<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_conflict_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('vessel_id')->index();
            $table->uuid('reporter_candidate_id')->nullable()->index();
            $table->uuid('target_candidate_id')->nullable()->index();
            $table->string('category', 30);
            $table->unsignedTinyInteger('rating')->default(1);
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_suspicious')->default(false);
            $table->string('suspicion_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->index(['company_id', 'vessel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_conflict_reports');
    }
};

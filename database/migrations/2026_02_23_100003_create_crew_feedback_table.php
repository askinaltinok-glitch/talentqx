<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crew_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_contract_id')->index();
            $table->uuid('pool_candidate_id')->index();
            $table->uuid('vessel_id')->index();
            $table->string('feedback_type', 20);
            $table->uuid('rated_by_user_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->unsignedTinyInteger('rating_overall');
            $table->unsignedTinyInteger('rating_competence')->nullable();
            $table->unsignedTinyInteger('rating_teamwork')->nullable();
            $table->unsignedTinyInteger('rating_reliability')->nullable();
            $table->unsignedTinyInteger('rating_communication')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedInteger('report_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['candidate_contract_id', 'feedback_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_feedback');
    }
};

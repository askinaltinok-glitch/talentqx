<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_session_analyses')) {
            Schema::create('interview_session_analyses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('session_id')->unique();
                $table->decimal('overall_score', 5, 2)->default(0);
                $table->json('dimension_scores')->nullable();
                $table->json('question_analyses')->nullable();
                $table->json('behavior_analysis')->nullable();
                $table->json('risk_flags')->nullable();
                $table->text('summary')->nullable();
                $table->json('recommendations')->nullable();
                $table->json('raw_response')->nullable();
                $table->string('model_version')->nullable();
                $table->timestamp('analyzed_at')->nullable();
                $table->timestamps();

                $table->foreign('session_id')->references('id')->on('interview_sessions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_session_analyses');
    }
};

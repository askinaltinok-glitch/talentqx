<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_assessment_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assessment_id')->index();
            $table->uuid('question_id')->index();
            $table->unsignedTinyInteger('value'); // 1..5

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('assessment_id')->references('id')->on('org_assessments')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('org_questions')->cascadeOnDelete();

            $table->unique(['assessment_id', 'question_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_assessment_answers');
    }
};

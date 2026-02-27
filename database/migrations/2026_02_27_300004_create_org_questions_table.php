<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('questionnaire_id')->index();

            $table->string('dimension')->index(); // planning|social|cooperation|stability|adaptability
            $table->boolean('is_reverse')->default(false);
            $table->unsignedInteger('sort_order');
            $table->json('text');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('questionnaire_id')->references('id')->on('org_questionnaires')->cascadeOnDelete();
            $table->unique(['questionnaire_id', 'sort_order']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_questions');
    }
};

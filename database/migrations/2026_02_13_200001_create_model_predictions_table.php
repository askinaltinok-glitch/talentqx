<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id');
            $table->string('model_version', 32);
            $table->integer('predicted_outcome_score');
            $table->string('predicted_label', 16); // GOOD/BAD/UNKNOWN
            $table->json('explain_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->onDelete('cascade');

            $table->index(['model_version', 'created_at']);
            $table->unique(['form_interview_id', 'model_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_predictions');
    }
};

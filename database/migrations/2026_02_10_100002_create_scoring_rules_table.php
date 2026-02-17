<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_tr', 100);
            $table->string('name_en', 100);
            $table->string('score_type', 50); // primary, risk, final
            $table->integer('weight_percent')->default(0);
            $table->json('source_competencies'); // which competencies feed this score
            $table->string('formula')->nullable(); // calculation formula
            $table->integer('min_value')->default(0);
            $table->integer('max_value')->default(100);
            $table->integer('warning_threshold')->nullable();
            $table->integer('critical_threshold')->nullable();
            $table->json('display_labels'); // score range labels
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('score_type');
        });

        // Decision matrix table
        Schema::create('decision_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('decision', 20); // HIRE, HOLD, REJECT
            $table->string('label_tr', 50);
            $table->string('label_en', 50);
            $table->json('conditions'); // array of conditions
            $table->string('color', 10);
            $table->string('icon', 50)->nullable();
            $table->text('description_tr')->nullable();
            $table->integer('priority')->default(0); // evaluation order
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_rules');
        Schema::dropIfExists('scoring_rules');
    }
};

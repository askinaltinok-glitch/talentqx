<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master competency library
        // Competencies can be reused across multiple positions
        Schema::create('competencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 100)->unique(); // e.g., 'COMMUNICATION', 'PROBLEM_SOLVING'
            $table->string('name_tr');
            $table->string('name_en');
            $table->text('description_tr')->nullable();
            $table->text('description_en')->nullable();
            $table->string('category', 50)->nullable(); // 'soft_skill', 'hard_skill', 'technical', 'behavioral'
            $table->string('icon', 50)->nullable();
            $table->json('indicators')->nullable(); // Observable behaviors that demonstrate this competency
            $table->json('evaluation_criteria')->nullable(); // How to score 1-5 for this competency
            $table->json('red_flags')->nullable(); // What indicates lack of this competency
            $table->boolean('is_universal')->default(false); // True if applies to all positions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('category');
            $table->index('is_universal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencies');
    }
};

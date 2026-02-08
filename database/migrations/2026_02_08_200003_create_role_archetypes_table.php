<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Role archetypes define seniority/responsibility levels
        // e.g., Entry-Level, Specialist, Coordinator, Manager, Leader, Executive
        Schema::create('role_archetypes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique(); // e.g., 'ENTRY', 'SPECIALIST', 'MANAGER'
            $table->string('name_tr');
            $table->string('name_en');
            $table->text('description_tr')->nullable();
            $table->text('description_en')->nullable();
            $table->integer('level')->default(1); // 1=Entry, 2=Specialist, 3=Coordinator, 4=Manager, 5=Leader, 6=Executive
            $table->json('typical_competencies')->nullable(); // Default competencies for this archetype
            $table->json('interview_focus')->nullable(); // What to focus on during interviews
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_archetypes');
    }
};

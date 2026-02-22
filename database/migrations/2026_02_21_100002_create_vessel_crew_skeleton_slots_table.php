<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_crew_skeleton_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vessel_id')->index();
            $table->string('slot_role', 40);          // MASTER, CHIEF_ENGINEER, etc.
            $table->uuid('candidate_id')->nullable();  // assigned crew member
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vessel_id', 'slot_role', 'is_active'], 'vessel_slot_role_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_crew_skeleton_slots');
    }
};

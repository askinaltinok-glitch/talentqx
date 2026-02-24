<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maritime_role_dna', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role_key', 50);
            $table->json('dna_dimensions');
            $table->json('behavioral_profile');
            $table->json('mismatch_signals');
            $table->json('integration_rules')->nullable();
            $table->string('version', 10)->default('v1');
            $table->timestamps();

            $table->foreign('role_key')
                ->references('role_key')
                ->on('maritime_roles')
                ->cascadeOnDelete();

            $table->unique(['role_key', 'version']);
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maritime_role_dna');
    }
};

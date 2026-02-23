<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_vessels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('vessel_id');
            $table->enum('role', ['owner', 'operator', 'manager'])->default('operator');
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('vessel_id')->references('id')->on('vessels')->cascadeOnDelete();
            $table->unique(['company_id', 'vessel_id']);
            $table->index('vessel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_vessels');
    }
};

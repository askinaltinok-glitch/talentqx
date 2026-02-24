<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maritime_roles', function (Blueprint $table) {
            $table->string('role_key', 50)->primary();
            $table->string('label', 100);
            $table->string('department', 30);
            $table->string('domain', 30)->default('maritime');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(100);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('department');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maritime_roles');
    }
};

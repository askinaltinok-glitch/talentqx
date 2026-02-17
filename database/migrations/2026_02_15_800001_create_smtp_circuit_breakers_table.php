<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_circuit_breakers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->unsignedInteger('failures')->default(0);
            $table->unsignedInteger('successes')->default(0);
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('opened_until')->nullable();
            $table->enum('state', ['closed', 'open', 'half_open'])->default('closed');
            $table->timestamps();

            $table->index('state');
            $table->index('opened_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_circuit_breakers');
    }
};

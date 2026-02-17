<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('job_contexts')) {
            Schema::create('job_contexts', function (Blueprint $table) {
                $table->id();
                $table->string('role_key');
                $table->string('context_key');
                $table->string('label_tr');
                $table->string('label_en');
                $table->text('description_tr')->nullable();
                $table->text('description_en')->nullable();
                $table->json('weight_multipliers')->nullable();
                $table->string('risk_level')->default('medium');
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->unique(['role_key', 'context_key']);
                $table->index('role_key');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_contexts');
    }
};

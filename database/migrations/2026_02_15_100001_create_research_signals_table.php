<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('research_company_id')->constrained('research_companies')->cascadeOnDelete();
            $table->string('signal_type', 64);
            $table->unsignedTinyInteger('confidence_score')->default(0);
            $table->string('source_url')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();

            $table->index('signal_type');
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_signals');
    }
};

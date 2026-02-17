<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interview_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version', 10)->default('v1');
            $table->string('language', 5)->default('tr');
            $table->string('position_code', 100)->nullable(); // null for generic template
            $table->string('title', 200)->nullable();
            $table->longText('template_json'); // Store exact JSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint: position_code + language + version
            $table->unique(['position_code', 'language', 'version'], 'interview_templates_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_templates');
    }
};

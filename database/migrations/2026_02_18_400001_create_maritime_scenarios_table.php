<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maritime_scenarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scenario_code', 50)->unique();
            $table->string('command_class', 30)->index();
            $table->unsignedTinyInteger('slot');
            $table->string('domain', 20)->index();
            $table->string('primary_capability', 20);
            $table->json('secondary_capabilities')->nullable();
            $table->unsignedTinyInteger('difficulty_tier');
            $table->json('briefing_json');
            $table->text('decision_prompt')->nullable();
            $table->json('decision_prompt_i18n')->nullable();
            $table->json('evaluation_axes_json');
            $table->json('critical_omission_flags_json');
            $table->json('expected_references_json')->nullable();
            $table->json('red_flags_json')->nullable();
            $table->string('version', 10)->default('v2');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['command_class', 'slot']);
            $table->index(['command_class', 'is_active']);
            $table->unique(['command_class', 'slot', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maritime_scenarios');
    }
};

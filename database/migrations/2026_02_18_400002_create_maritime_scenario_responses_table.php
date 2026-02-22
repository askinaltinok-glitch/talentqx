<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maritime_scenario_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id');
            $table->uuid('scenario_id');
            $table->unsignedTinyInteger('slot');
            $table->text('raw_answer_text');
            $table->json('structured_actions_json')->nullable();
            $table->json('regulation_mentions_json')->nullable();
            $table->timestamps();

            $table->unique(['form_interview_id', 'slot']);
            $table->index('form_interview_id');
            $table->index('scenario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maritime_scenario_responses');
    }
};

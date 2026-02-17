<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_interview_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id');

            $table->unsignedTinyInteger('slot');      // 1..8
            $table->string('competency', 64);         // communication...
            $table->longText('answer_text');          // raw answer

            $table->timestamps();

            $table->foreign('form_interview_id')
                ->references('id')
                ->on('form_interviews')
                ->onDelete('cascade');

            $table->unique(['form_interview_id', 'slot']); // upsert by slot
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_interview_answers');
    }
};

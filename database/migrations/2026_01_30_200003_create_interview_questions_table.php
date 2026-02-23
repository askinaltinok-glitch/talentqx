<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('interview_questions')) {
            Schema::create('interview_questions', function (Blueprint $table) {
                $table->id();
                $table->string('role_key');
                $table->string('context_key')->nullable();
                $table->string('question_key');
                $table->string('locale', 5)->default('tr');
                $table->text('question_text');
                $table->string('dimension')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['role_key', 'context_key', 'locale']);
                $table->unique(['role_key', 'context_key', 'question_key', 'locale'], 'iq_role_ctx_qkey_locale_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
    }
};

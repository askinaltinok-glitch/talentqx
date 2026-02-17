<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('form_interview_answers', 'score')) {
            Schema::table('form_interview_answers', function (Blueprint $table) {
                $table->unsignedTinyInteger('score')->nullable()->after('answer_text')
                    ->comment('Heuristic score 0-5 (maritime) or 0-95 (general)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('form_interview_answers', 'score')) {
            Schema::table('form_interview_answers', function (Blueprint $table) {
                $table->dropColumn('score');
            });
        }
    }
};

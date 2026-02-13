<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_outcomes', function (Blueprint $table) {
            $table->unsignedTinyInteger('outcome_score')
                ->nullable()
                ->after('performance_rating')
                ->comment('Computed 0-100 score for ML learning');

            $table->index('outcome_score');
        });
    }

    public function down(): void
    {
        Schema::table('interview_outcomes', function (Blueprint $table) {
            $table->dropIndex(['outcome_score']);
            $table->dropColumn('outcome_score');
        });
    }
};

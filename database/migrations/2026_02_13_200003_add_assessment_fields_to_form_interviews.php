<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->string('english_assessment_status', 16)->nullable()->after('industry_code');
            $table->integer('english_assessment_score')->nullable()->after('english_assessment_status');
            $table->string('video_assessment_status', 16)->nullable()->after('english_assessment_score');
            $table->text('video_assessment_url')->nullable()->after('video_assessment_status');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropColumn([
                'english_assessment_status',
                'english_assessment_score',
                'video_assessment_status',
                'video_assessment_url',
            ]);
        });
    }
};

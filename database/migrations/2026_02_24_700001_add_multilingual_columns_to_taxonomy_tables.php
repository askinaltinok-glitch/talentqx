<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add DE, FR, AR language columns to taxonomy tables.
     * Run with: php artisan migrate --database=mysql_talentqx
     */
    public function up(): void
    {
        // position_questions — question + follow_up columns
        Schema::table('position_questions', function (Blueprint $table) {
            $table->text('question_de')->nullable()->after('question_en');
            $table->text('question_fr')->nullable()->after('question_de');
            $table->text('question_ar')->nullable()->after('question_fr');
            $table->text('follow_up_de')->nullable()->after('follow_up_en');
            $table->text('follow_up_fr')->nullable()->after('follow_up_de');
            $table->text('follow_up_ar')->nullable()->after('follow_up_fr');
        });

        // job_positions — name + description columns
        Schema::table('job_positions', function (Blueprint $table) {
            $table->string('name_de')->nullable()->after('name_en');
            $table->string('name_fr')->nullable()->after('name_de');
            $table->string('name_ar')->nullable()->after('name_fr');
            $table->text('description_de')->nullable()->after('description_en');
            $table->text('description_fr')->nullable()->after('description_de');
            $table->text('description_ar')->nullable()->after('description_fr');
        });

        // job_domains — name columns
        Schema::table('job_domains', function (Blueprint $table) {
            $table->string('name_de')->nullable()->after('name_en');
            $table->string('name_fr')->nullable()->after('name_de');
            $table->string('name_ar')->nullable()->after('name_fr');
        });

        // job_subdomains — name columns
        Schema::table('job_subdomains', function (Blueprint $table) {
            $table->string('name_de')->nullable()->after('name_en');
            $table->string('name_fr')->nullable()->after('name_de');
            $table->string('name_ar')->nullable()->after('name_fr');
        });
    }

    public function down(): void
    {
        Schema::table('position_questions', function (Blueprint $table) {
            $table->dropColumn(['question_de', 'question_fr', 'question_ar', 'follow_up_de', 'follow_up_fr', 'follow_up_ar']);
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn(['name_de', 'name_fr', 'name_ar', 'description_de', 'description_fr', 'description_ar']);
        });

        Schema::table('job_domains', function (Blueprint $table) {
            $table->dropColumn(['name_de', 'name_fr', 'name_ar']);
        });

        Schema::table('job_subdomains', function (Blueprint $table) {
            $table->dropColumn(['name_de', 'name_fr', 'name_ar']);
        });
    }
};

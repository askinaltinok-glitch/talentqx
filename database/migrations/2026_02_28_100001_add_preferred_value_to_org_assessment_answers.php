<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('org_assessment_answers', function (Blueprint $table) {
            $table->unsignedTinyInteger('preferred_value')->nullable()->after('value');
        });
    }

    public function down(): void {
        Schema::table('org_assessment_answers', function (Blueprint $table) {
            $table->dropColumn('preferred_value');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->uuid('vessel_id')->nullable()->after('vessel_type');
            $table->index('vessel_id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn('vessel_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->timestamp('joined_at')->nullable()->after('scheduled_at');
            $table->unsignedSmallInteger('late_minutes')->nullable()->after('joined_at');
            $table->timestamp('no_show_marked_at')->nullable()->after('late_minutes');

            $table->index(['scheduled_at', 'joined_at']);
            $table->index(['no_show_marked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropIndex(['interviews_scheduled_at_joined_at_index']);
            $table->dropIndex(['interviews_no_show_marked_at_index']);

            $table->dropColumn(['joined_at', 'late_minutes', 'no_show_marked_at']);
        });
    }
};

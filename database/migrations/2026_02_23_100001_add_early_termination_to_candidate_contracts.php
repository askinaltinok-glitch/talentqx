<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->boolean('early_termination')->default(false)->after('notes');
            $table->string('termination_reason', 100)->nullable()->after('early_termination');
            $table->date('original_end_date')->nullable()->after('termination_reason');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_contracts', function (Blueprint $table) {
            $table->dropColumn(['early_termination', 'termination_reason', 'original_end_date']);
        });
    }
};

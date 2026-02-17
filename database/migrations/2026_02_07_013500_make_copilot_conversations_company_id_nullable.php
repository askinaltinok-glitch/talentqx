<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copilot_conversations', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['company_id']);

            // Make company_id nullable
            $table->uuid('company_id')->nullable()->change();

            // Re-add foreign key with nullable support
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('copilot_conversations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);

            $table->uuid('company_id')->nullable(false)->change();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_weights', function (Blueprint $table) {
            $table->boolean('is_frozen')->default(false)->after('is_active');
            $table->timestamp('frozen_at')->nullable()->after('is_frozen');
            $table->text('frozen_notes')->nullable()->after('frozen_at');

            $table->index(['is_active', 'is_frozen']);
        });
    }

    public function down(): void
    {
        Schema::table('model_weights', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_frozen']);
            $table->dropColumn(['is_frozen', 'frozen_at', 'frozen_notes']);
        });
    }
};

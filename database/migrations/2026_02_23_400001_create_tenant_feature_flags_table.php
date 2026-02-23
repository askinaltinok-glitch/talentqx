<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->string('feature_key', 120);
            $table->boolean('is_enabled')->default(false);

            $table->json('payload')->nullable();

            $table->timestamp('enabled_at')->nullable();
            $table->uuid('enabled_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'feature_key']);
            $table->index(['feature_key', 'is_enabled']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_flags');
    }
};

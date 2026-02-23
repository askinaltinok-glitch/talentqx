<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synergy_weight_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scope', 20)->default('global'); // global|company
            $table->uuid('company_id')->nullable()->index();
            $table->json('weights_json');
            $table->json('deltas_json')->nullable();
            $table->json('audit_log_json')->nullable();
            $table->string('last_training_window', 20)->nullable(); // e.g. "90d"
            $table->unsignedInteger('sample_size')->default(0);
            $table->timestamps();

            $table->unique(['scope', 'company_id'], 'sws_scope_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synergy_weight_sets');
    }
};

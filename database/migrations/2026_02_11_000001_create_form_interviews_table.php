<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_interviews', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('version', 32);              // v1
            $table->string('language', 8);              // tr/en
            $table->string('position_code', 128);       // retail_cashier / __generic__
            $table->string('template_position_code', 128); // resolved template used (could be __generic__)
            $table->string('status', 24)->default('draft'); // draft|in_progress|completed

            // store exact template JSON string that was used (snapshot)
            $table->longText('template_json')->nullable();

            // optional metadata
            $table->json('meta')->nullable();           // candidate_id, tenant_id, etc

            // scoring snapshots
            $table->json('competency_scores')->nullable(); // {communication:.., ...}
            $table->json('risk_flags')->nullable();        // [{code,severity,evidence...}]
            $table->integer('final_score')->nullable();    // 0-100
            $table->string('decision', 16)->nullable();    // HIRE|HOLD|REJECT
            $table->text('decision_reason')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['version','language','position_code']);
            $table->index(['status','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_interviews');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolver_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_interview_id');
            $table->uuid('candidate_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->unsignedTinyInteger('phase'); // 1 or 2
            $table->json('input_snapshot');
            $table->json('class_detection_output')->nullable();
            $table->json('scenario_set_json')->nullable();
            $table->text('selection_reason')->nullable();
            $table->json('capability_output')->nullable();
            $table->json('final_packet')->nullable();
            $table->timestamp('created_at')->nullable();
            // No updated_at — append-only

            $table->index('form_interview_id');
            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolver_audit_logs');
    }
};

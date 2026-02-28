<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_pulse_risk_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->uuid('pulse_profile_id')->index();

            $table->tinyInteger('risk_score')->unsigned()->default(0);
            $table->enum('risk_level', ['low', 'moderate', 'elevated'])->default('low');
            $table->json('drivers')->nullable();
            $table->json('suggestions')->nullable();

            $table->timestamp('computed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('org_employees')->cascadeOnDelete();
            $table->foreign('pulse_profile_id')->references('id')->on('org_pulse_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_pulse_risk_snapshots');
    }
};

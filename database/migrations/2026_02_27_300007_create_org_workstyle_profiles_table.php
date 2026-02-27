<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_workstyle_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->uuid('assessment_id')->unique()->index();

            $table->decimal('planning_score', 5, 2);
            $table->decimal('social_score', 5, 2);
            $table->decimal('cooperation_score', 5, 2);
            $table->decimal('stability_score', 5, 2);
            $table->decimal('adaptability_score', 5, 2);

            $table->timestamp('computed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('org_employees')->cascadeOnDelete();
            $table->foreign('assessment_id')->references('id')->on('org_assessments')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_workstyle_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->uuid('questionnaire_id')->index();

            $table->string('status')->default('started')->index(); // started|completed|abandoned
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('next_due_at')->nullable()->index();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('org_employees')->cascadeOnDelete();
            $table->foreign('questionnaire_id')->references('id')->on('org_questionnaires')->cascadeOnDelete();
            $table->index(['tenant_id', 'employee_id', 'questionnaire_id', 'status'], 'org_assess_tenant_emp_quest_status_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_assessments');
    }
};

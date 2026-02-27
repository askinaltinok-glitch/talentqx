<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_employee_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();

            $table->string('consent_version'); // "orghealth_v1"
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('delete_requested_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('org_employees')->cascadeOnDelete();
            $table->index(['tenant_id', 'employee_id', 'consent_version'], 'org_consents_tenant_emp_ver_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_employee_consents');
    }
};

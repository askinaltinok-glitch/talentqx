<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();

            $table->string('external_employee_ref')->nullable();
            $table->string('full_name');
            $table->string('email')->nullable()->index();
            $table->string('phone_e164')->nullable()->index();

            $table->string('department_code')->nullable()->index();
            $table->string('position_code')->nullable()->index();

            $table->string('status')->default('active')->index(); // active|inactive|left

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_employees');
    }
};

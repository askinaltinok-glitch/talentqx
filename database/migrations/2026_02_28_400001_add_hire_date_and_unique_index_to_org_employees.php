<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('org_employees', function (Blueprint $table) {
            $table->date('hire_date')->nullable()->after('position_code');
        });

        // Clean up duplicates before adding unique index:
        // Keep the first row (by id) for each (tenant_id, external_employee_ref) pair
        $duplicates = DB::select("
            SELECT e.id FROM org_employees e
            INNER JOIN (
                SELECT tenant_id, external_employee_ref, MIN(id) as keep_id
                FROM org_employees
                WHERE external_employee_ref IS NOT NULL
                GROUP BY tenant_id, external_employee_ref
                HAVING COUNT(*) > 1
            ) d ON e.tenant_id = d.tenant_id
              AND e.external_employee_ref = d.external_employee_ref
              AND e.id != d.keep_id
        ");

        if (count($duplicates) > 0) {
            $ids = array_map(fn($r) => $r->id, $duplicates);
            DB::table('org_employees')->whereIn('id', $ids)->update([
                'external_employee_ref' => DB::raw("CONCAT(external_employee_ref, '-dup-', LEFT(id, 8))"),
            ]);
        }

        Schema::table('org_employees', function (Blueprint $table) {
            // Composite unique: only where external_employee_ref is not null
            // MySQL unique indexes ignore NULLs, so this works naturally
            $table->unique(['tenant_id', 'external_employee_ref'], 'org_employees_tenant_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('org_employees', function (Blueprint $table) {
            $table->dropUnique('org_employees_tenant_ref_unique');
            $table->dropColumn('hire_date');
        });
    }
};

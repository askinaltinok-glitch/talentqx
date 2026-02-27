<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('org_questionnaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index(); // null = global template

            $table->string('code');    // workstyle
            $table->string('version'); // v1
            $table->string('status')->default('draft')->index(); // draft|active|retired

            $table->json('title');
            $table->json('description')->nullable();
            $table->json('scoring_schema');

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['tenant_id', 'code', 'version']);
            $table->index(['tenant_id', 'code', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('org_questionnaires');
    }
};

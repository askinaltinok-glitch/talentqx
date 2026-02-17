<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_crew_members', function (Blueprint $table) {
            $table->id();

            $table->char('company_id', 36);  // crm_companies.id is UUID
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('role_code')->nullable();     // captain, chief_officer, second_engineer...
            $table->string('department')->nullable();    // deck | engine | galley
            $table->string('vessel_id')->nullable();     // optional
            $table->string('nationality', 2)->nullable();// AZ/TR etc
            $table->string('language', 5)->nullable();   // az/tr/en

            $table->timestamps();

            $table->index(['company_id', 'department']);
            $table->index(['company_id', 'role_code']);
            $table->index(['company_id', 'email']);
            $table->index(['company_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_crew_members');
    }
};

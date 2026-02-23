<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crew_member_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('crew_member_id');
            $table->string('certificate_type', 32);
            $table->string('certificate_code', 100)->nullable();
            $table->string('issuing_country', 5)->nullable();
            $table->date('expires_at')->nullable();
            $table->string('expiry_source', 24)->default('uploaded');
            $table->timestamps();

            $table->foreign('crew_member_id')
                ->references('id')
                ->on('company_crew_members')
                ->cascadeOnDelete();

            $table->index(['crew_member_id', 'certificate_type']);
            $table->unique(
                ['crew_member_id', 'certificate_type', 'issuing_country'],
                'crew_cert_member_type_country_unique'
            );
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crew_member_certificates');
    }
};

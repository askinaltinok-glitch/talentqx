<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_certificate_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('company_id');
            $table->string('certificate_type', 32);
            $table->unsignedSmallInteger('validity_months');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'certificate_type']);
            $table->index('company_id');
            $table->index('certificate_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_certificate_rules');
    }
};

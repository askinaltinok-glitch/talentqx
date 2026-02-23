<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_certificate_rules', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->string('certificate_type', 32);
            $table->unsignedSmallInteger('validity_months');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'certificate_type']);
            $table->index('country_code');
            $table->index('certificate_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_certificate_rules');
    }
};

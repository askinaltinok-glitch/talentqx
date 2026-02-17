<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();

            $table->string('industry_code');       // 'general' | 'maritime'
            $table->string('title');
            $table->string('slug')->unique();

            $table->string('company_name')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable();

            $table->longText('description')->nullable();
            $table->longText('requirements')->nullable();

            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['industry_code', 'is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};

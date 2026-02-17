<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained('job_listings')->cascadeOnDelete();

            $table->unsignedBigInteger('candidate_id')->nullable();

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();

            $table->string('source')->default('job_apply');
            $table->string('status')->default('new');

            $table->boolean('consent_terms')->default(false);
            $table->boolean('consent_contact')->default(false);

            $table->string('ip_hash')->nullable();
            $table->string('ua_hash')->nullable();

            $table->timestamps();

            $table->index(['job_listing_id', 'status', 'created_at']);
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};

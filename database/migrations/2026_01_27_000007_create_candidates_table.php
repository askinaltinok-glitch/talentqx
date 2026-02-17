<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('cv_url', 500)->nullable();
            $table->json('cv_parsed_data')->nullable();
            $table->decimal('cv_match_score', 5, 2)->nullable();
            $table->string('source', 100)->nullable();
            $table->string('referrer_name')->nullable();
            $table->string('status', 50)->default('applied');
            $table->timestamp('status_changed_at')->nullable();
            $table->uuid('status_changed_by')->nullable();
            $table->text('status_note')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->string('consent_version', 20)->nullable();
            $table->timestamp('consent_given_at')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('job_postings')->onDelete('cascade');
            $table->foreign('status_changed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('job_id');
            $table->index('email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};

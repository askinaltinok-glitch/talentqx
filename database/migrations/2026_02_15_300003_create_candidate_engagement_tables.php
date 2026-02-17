<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Profile views tracking
        Schema::create('candidate_profile_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('viewer_type', 32); // company, admin
            $table->uuid('viewer_id')->nullable();
            $table->string('viewer_name', 255)->nullable();
            $table->string('context', 64); // presentation, search, browse
            $table->json('context_meta')->nullable(); // {talent_request_id, vessel_name, role}
            $table->timestamp('viewed_at');

            $table->foreign('pool_candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
            $table->index('pool_candidate_id');
            $table->index('viewed_at');
        });

        // Candidate notifications (tiered)
        Schema::create('candidate_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id');
            $table->string('type', 64); // profile_viewed, role_viewed, vessel_viewed, status_changed, review_approved
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->string('tier_required', 16)->default('free'); // free, plus, pro
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at');

            $table->foreign('pool_candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
            $table->index(['pool_candidate_id', 'read_at']);
            $table->index('tier_required');
        });

        // Vessel reviews (anonymous)
        Schema::create('vessel_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pool_candidate_id'); // author
            $table->string('company_name', 255);
            $table->string('vessel_name', 255)->nullable();
            $table->string('vessel_type', 64)->nullable(); // tanker, bulk, container, offshore, etc
            $table->unsignedTinyInteger('rating_salary'); // 1-5
            $table->unsignedTinyInteger('rating_provisions'); // 1-5
            $table->unsignedTinyInteger('rating_cabin'); // 1-5
            $table->unsignedTinyInteger('rating_internet'); // 1-5
            $table->unsignedTinyInteger('rating_bonus'); // 1-5
            $table->decimal('overall_rating', 2, 1); // computed avg
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->string('status', 32)->default('pending'); // pending, approved, rejected
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('pool_candidate_id')->references('id')->on('pool_candidates')->cascadeOnDelete();
            $table->index('company_name');
            $table->index('status');
            $table->index('pool_candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_reviews');
        Schema::dropIfExists('candidate_notifications');
        Schema::dropIfExists('candidate_profile_views');
    }
};

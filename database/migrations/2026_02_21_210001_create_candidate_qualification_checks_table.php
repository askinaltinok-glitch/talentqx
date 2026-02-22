<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_qualification_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->string('qualification_key', 50);
            $table->enum('status', ['self_declared', 'uploaded', 'verified', 'rejected'])->default('self_declared');
            $table->text('evidence_url')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'qualification_key'], 'cqc_candidate_qual_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_qualification_checks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavioral_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->index();
            $table->uuid('company_id')->nullable()->index();
            $table->uuid('interview_id')->nullable()->index();
            $table->string('language', 5)->default('en');
            $table->string('version', 10)->default('v1');
            $table->string('status', 20)->default('partial'); // partial|final
            $table->decimal('confidence', 5, 2)->default(0.00); // 0.00 - 1.00
            $table->json('dimensions_json'); // 7 dims {DIM: {score, level, evidence[], flags[]}}
            $table->json('fit_json')->nullable(); // vessel/command-class fit map
            $table->json('flags_json')->nullable(); // manipulation/contradiction flags
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavioral_profiles');
    }
};

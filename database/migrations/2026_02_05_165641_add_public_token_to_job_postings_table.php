<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('public_token', 32)->nullable()->unique()->after('slug');
            $table->boolean('qr_enabled')->default(false)->after('public_token');
        });

        // Generate tokens for existing jobs
        $jobs = DB::table('job_postings')->whereNull('public_token')->get();
        foreach ($jobs as $job) {
            DB::table('job_postings')
                ->where('id', $job->id)
                ->update(['public_token' => Str::random(12)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn(['public_token', 'qr_enabled']);
        });
    }
};

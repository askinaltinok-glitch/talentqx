<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('industry_code', 32)->default('general'); // general, maritime, ...
            $table->string('name', 255);
            $table->char('country_code', 2);
            $table->string('city', 128)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('domain', 255)->nullable()->unique(); // extracted from website
            $table->string('linkedin_url', 500)->nullable();
            $table->string('company_type', 64)->nullable();
            // ship_manager, ship_owner, agency, charterer, retail, factory, manning_agent, training_center, etc.
            $table->string('size_band', 16)->nullable(); // 1-10, 11-50, 51-200, 200+
            $table->json('tags')->nullable();
            $table->json('data_sources')->nullable(); // [{url, type, date}]
            $table->string('status', 32)->default('new');
            // new, qualified, contacted, active_client, archived
            $table->uuid('owner_user_id')->nullable();
            $table->timestamps();

            $table->index('industry_code');
            $table->index('country_code');
            $table->index('company_type');
            $table->index('status');
            $table->index('owner_user_id');
            $table->index(['industry_code', 'status']);
            $table->index(['industry_code', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_companies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('industry_code', 32)->default('general');
            $table->string('source_channel', 64);
            // website_form, inbound_email, referral, research_agent, demo, manual
            $table->json('source_meta')->nullable(); // {campaign, utm_source, page, ref, ...}
            $table->uuid('company_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->string('lead_name', 500); // "Company - Contact"
            $table->string('stage', 32)->default('new');
            // new, contacted, meeting, proposal, negotiation, won, lost
            $table->string('priority', 16)->default('med'); // low, med, high
            $table->text('notes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('crm_companies')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->onDelete('set null');

            $table->index('industry_code');
            $table->index('source_channel');
            $table->index('stage');
            $table->index('priority');
            $table->index('company_id');
            $table->index('contact_id');
            $table->index('last_activity_at');
            $table->index(['industry_code', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};

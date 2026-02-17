<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id');
            $table->uuid('company_id')->nullable();
            $table->uuid('contact_id')->nullable();
            $table->string('industry_code', 32)->default('general');
            $table->string('deal_name', 255);
            $table->string('stage', 64);
            $table->decimal('value', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_at')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->text('lost_reason')->nullable();
            $table->uuid('owner_user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('crm_leads')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('crm_companies')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->nullOnDelete();

            $table->index('lead_id');
            $table->index('company_id');
            $table->index(['industry_code', 'stage']);
            $table->index('expected_close_at');
            $table->index('stage');
        });

        Schema::create('crm_deal_stage_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('deal_id');
            $table->string('from_stage', 64)->nullable();
            $table->string('to_stage', 64);
            $table->uuid('changed_by')->nullable();
            $table->timestamp('created_at');

            $table->foreign('deal_id')->references('id')->on('crm_deals')->cascadeOnDelete();
            $table->index('deal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_deal_stage_history');
        Schema::dropIfExists('crm_deals');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('package_id')->nullable()->constrained('credit_packages')->onDelete('set null');
            $table->string('payment_provider')->default('iyzico'); // iyzico, stripe, etc.
            $table->string('payment_id')->nullable()->index();     // Provider transaction ID
            $table->string('conversation_id')->nullable()->index(); // İyzico conversation ID
            $table->string('status')->default('pending');          // pending/processing/completed/failed/refunded
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('TRY');
            $table->integer('credits_added')->default(0);
            $table->json('provider_response')->nullable();         // Raw provider response
            $table->json('metadata')->nullable();                  // Additional info
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

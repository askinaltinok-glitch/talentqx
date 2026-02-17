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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->string('parasut_invoice_id')->nullable()->index(); // Paraşüt fatura ID
            $table->string('status')->default('draft');              // draft/sent/paid/cancelled
            $table->decimal('subtotal', 10, 2);                      // Vergi öncesi tutar
            $table->decimal('tax_rate', 5, 2)->default(20.00);       // KDV oranı %
            $table->decimal('tax_amount', 10, 2);                    // KDV tutarı
            $table->decimal('total_amount', 10, 2);                  // Toplam tutar
            $table->string('currency', 3)->default('TRY');
            $table->date('issue_date');
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->json('line_items')->nullable();                  // Fatura kalemleri
            $table->json('billing_info')->nullable();                // Fatura adresi snapshot
            $table->string('pdf_url')->nullable();
            $table->string('pdf_path')->nullable();                  // Local PDF path
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

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
        Schema::table('companies', function (Blueprint $table) {
            // Billing info fields
            $table->string('legal_name')->nullable()->after('name');           // Yasal şirket adı
            $table->string('tax_number', 20)->nullable()->after('legal_name'); // Vergi no
            $table->string('tax_office', 100)->nullable()->after('tax_number'); // Vergi dairesi
            $table->string('billing_type')->default('individual')->after('tax_office'); // individual/corporate
            $table->text('billing_address')->nullable()->after('billing_type'); // Fatura adresi
            $table->string('billing_city', 100)->nullable()->after('billing_address');
            $table->string('billing_postal_code', 10)->nullable()->after('billing_city');
            $table->string('billing_email')->nullable()->after('billing_postal_code'); // Fatura e-posta
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'tax_number',
                'tax_office',
                'billing_type',
                'billing_address',
                'billing_city',
                'billing_postal_code',
                'billing_email',
            ]);
        });
    }
};

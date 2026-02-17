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
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                              // Paket adı
            $table->string('slug')->unique();                    // URL slug
            $table->integer('credits');                          // Kontür sayısı
            $table->decimal('price_try', 10, 2);                 // TL fiyat
            $table->decimal('price_eur', 10, 2)->nullable();     // EUR fiyat
            $table->text('description')->nullable();             // Açıklama
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);      // Öne çıkan paket
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};

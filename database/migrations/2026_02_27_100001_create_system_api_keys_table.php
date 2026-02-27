<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service_name', 50)->unique();
            $table->text('api_key')->comment('encrypted');
            $table->text('secret_key')->nullable()->comment('encrypted');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_api_keys');
    }
};

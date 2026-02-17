<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create import batches table
        Schema::create('employee_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('company_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('filename');
            $table->integer('imported_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->boolean('is_rolled_back')->default(false);
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });

        // Add import_batch_id to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('import_batch_id')->nullable()->after('company_id');
            $table->foreign('import_batch_id')->references('id')->on('employee_import_batches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['import_batch_id']);
            $table->dropColumn('import_batch_id');
        });

        Schema::dropIfExists('employee_import_batches');
    }
};

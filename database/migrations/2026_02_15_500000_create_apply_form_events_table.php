<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apply_form_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_id', 64);
            $table->string('event_type', 32); // step_view, step_complete, abandon, submit
            $table->unsignedTinyInteger('step_number'); // 1=Personal, 2=Maritime, 3=Confirm
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->string('country_code', 4)->nullable();
            $table->string('source_channel', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->json('meta')->nullable(); // {field_errors, referrer, lang, device_type}
            $table->timestamp('created_at')->useCurrent();

            $table->index('session_id');
            $table->index(['event_type', 'step_number']);
            $table->index('created_at');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apply_form_events');
    }
};

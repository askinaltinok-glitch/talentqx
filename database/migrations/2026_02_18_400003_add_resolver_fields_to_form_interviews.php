<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('phase')->default(0)->after('deployment_packet_json');        // 0=legacy, 1=identity, 2=scenario
            $table->boolean('needs_review')->default(false)->after('phase');
            $table->string('resolver_status', 30)->nullable()->after('needs_review');                 // pending/resolved/overridden/blocked
            $table->uuid('linked_phase_interview_id')->nullable()->after('resolver_status');           // Phase-1↔Phase-2 link
            $table->json('scenario_set_json')->nullable()->after('linked_phase_interview_id');         // selected scenario IDs
            $table->string('override_class', 30)->nullable()->after('scenario_set_json');
            $table->uuid('override_by_user_id')->nullable()->after('override_class');

            $table->index('needs_review');
            $table->index('resolver_status');
        });
    }

    public function down(): void
    {
        Schema::table('form_interviews', function (Blueprint $table) {
            $table->dropIndex(['needs_review']);
            $table->dropIndex(['resolver_status']);
            $table->dropColumn([
                'phase',
                'needs_review',
                'resolver_status',
                'linked_phase_interview_id',
                'scenario_set_json',
                'override_class',
                'override_by_user_id',
            ]);
        });
    }
};

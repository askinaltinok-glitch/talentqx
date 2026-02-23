<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessel_requirement_templates', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('is_active');  // draft | published
            $table->unsignedInteger('published_version')->default(1)->after('status');
            $table->json('draft_profile_json')->nullable()->after('profile_json');
            $table->json('version_history')->nullable()->after('published_version'); // [{version, profile_json, published_at, published_by}]
        });

        // Seed version_history for existing templates
        DB::table('vessel_requirement_templates')->orderBy('id')->each(function ($row) {
            DB::table('vessel_requirement_templates')
                ->where('id', $row->id)
                ->update([
                    'version_history' => json_encode([[
                        'version' => 1,
                        'profile_json' => json_decode($row->profile_json, true),
                        'published_at' => $row->updated_at ?? $row->created_at,
                        'published_by' => null,
                    ]]),
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('vessel_requirement_templates', function (Blueprint $table) {
            $table->dropColumn(['status', 'published_version', 'draft_profile_json', 'version_history']);
        });
    }
};

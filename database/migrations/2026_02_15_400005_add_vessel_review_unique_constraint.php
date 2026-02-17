<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Safety: check for duplicates before adding unique constraint
        $duplicates = DB::table('vessel_reviews')
            ->select('pool_candidate_id', 'company_name', 'vessel_name')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('pool_candidate_id', 'company_name', 'vessel_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            foreach ($duplicates as $dup) {
                Log::warning('Sprint7: Duplicate vessel reviews found', [
                    'pool_candidate_id' => $dup->pool_candidate_id,
                    'company_name' => $dup->company_name,
                    'vessel_name' => $dup->vessel_name,
                    'count' => $dup->cnt,
                ]);

                // Keep the newest, delete the rest
                $keepId = DB::table('vessel_reviews')
                    ->where('pool_candidate_id', $dup->pool_candidate_id)
                    ->where('company_name', $dup->company_name)
                    ->where(function ($q) use ($dup) {
                        if ($dup->vessel_name === null) {
                            $q->whereNull('vessel_name');
                        } else {
                            $q->where('vessel_name', $dup->vessel_name);
                        }
                    })
                    ->orderByDesc('created_at')
                    ->value('id');

                DB::table('vessel_reviews')
                    ->where('pool_candidate_id', $dup->pool_candidate_id)
                    ->where('company_name', $dup->company_name)
                    ->where(function ($q) use ($dup) {
                        if ($dup->vessel_name === null) {
                            $q->whereNull('vessel_name');
                        } else {
                            $q->where('vessel_name', $dup->vessel_name);
                        }
                    })
                    ->where('id', '!=', $keepId)
                    ->delete();
            }

            Log::info('Sprint7: Duplicate vessel reviews cleaned up');
        }

        Schema::table('vessel_reviews', function (Blueprint $table) {
            $table->unique(
                ['pool_candidate_id', 'company_name', 'vessel_name'],
                'uq_candidate_company_vessel'
            );
        });
    }

    public function down(): void
    {
        Schema::table('vessel_reviews', function (Blueprint $table) {
            $table->dropUnique('uq_candidate_company_vessel');
        });
    }
};

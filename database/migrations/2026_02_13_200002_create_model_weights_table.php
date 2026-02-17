<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_weights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model_version', 32)->unique();
            $table->json('weights_json');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Seed default weights v0
        DB::table('model_weights')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'model_version' => 'ml_v0',
            'weights_json' => json_encode([
                'base_weight' => 1.0,
                'risk_flag_penalties' => [
                    'RF_AGGRESSION' => -15,
                    'RF_ETHICS_CONCERN' => -12,
                    'RF_INCOMPLETE' => -8,
                    'RF_SPARSE' => -5,
                    'RF_LOW_MOTIVATION' => -5,
                    'RF_COMMUNICATION' => -4,
                    'default' => -3,
                ],
                'meta_penalties' => [
                    'sparse_answers' => -5,
                    'very_short_answers' => -3,
                    'incomplete_interview' => -10,
                ],
                'boosts' => [
                    'maritime_industry' => 3,
                    'referral_source' => 2,
                    'english_b2_plus' => 2,
                ],
                'thresholds' => [
                    'good' => 50,
                    'bad' => 50,
                ],
            ]),
            'notes' => 'Initial hand-tuned weights for MVP',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('model_weights');
    }
};

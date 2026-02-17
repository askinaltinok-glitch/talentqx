<?php

namespace Database\Factories;

use App\Models\FormInterview;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormInterviewFactory extends Factory
{
    protected $model = FormInterview::class;

    public function definition(): array
    {
        return [
            'template_id' => null,
            'template_version' => 'v1',
            'template_sha256' => hash('sha256', 'test'),
            'language' => 'tr',
            'position_code' => 'GEMI_YARDIMCI',
            'status' => 'completed',
            'calibrated_score' => $this->faker->numberBetween(30, 90),
            'confidence_score' => $this->faker->numberBetween(60, 100),
            'decision_recommendation' => $this->faker->randomElement(['HIRE', 'HOLD', 'REJECT']),
            'risk_flags_json' => [],
            'scoring_summary_json' => [],
            'industry_code' => 'maritime',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function maritime(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry_code' => 'maritime',
            'position_code' => 'GEMI_YARDIMCI',
        ]);
    }
}

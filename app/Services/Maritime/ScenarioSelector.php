<?php

namespace App\Services\Maritime;

use App\Exceptions\ScenarioNotFoundException;
use App\Models\MaritimeScenario;
use Illuminate\Support\Collection;

/**
 * Selects 8 active scenarios for a command class.
 *
 * Hard fail: if fewer than 8 active scenarios exist → ScenarioNotFoundException (422).
 * No placeholder. No partial sets.
 */
class ScenarioSelector
{
    private const REQUIRED_SLOTS = 8;

    /**
     * Select 8 active scenarios for a single command class.
     *
     * @throws ScenarioNotFoundException if < 8 active scenarios
     * @return Collection<MaritimeScenario>
     */
    public function select(string $commandClass): Collection
    {
        $scenarios = MaritimeScenario::active()
            ->forClass($commandClass)
            ->orderBy('slot')
            ->get();

        if ($scenarios->count() < self::REQUIRED_SLOTS) {
            throw new ScenarioNotFoundException(
                commandClass: $commandClass,
                required: self::REQUIRED_SLOTS,
                found: $scenarios->count(),
            );
        }

        return $scenarios->take(self::REQUIRED_SLOTS);
    }

    /**
     * Select with multi-class blending.
     *
     * When delta between top-2 classes < threshold, blend:
     *   - Primary class: slots 1-N (default 6)
     *   - Secondary class: slots N+1 to 8 (default 2)
     *
     * @throws ScenarioNotFoundException if either class has insufficient scenarios
     * @return Collection<MaritimeScenario>
     */
    public function selectWithBlending(
        string $primaryClass,
        string $secondaryClass,
    ): Collection {
        $primaryCount = (int) config('maritime.blending.primary', 6);
        $secondaryCount = (int) config('maritime.blending.secondary', 2);

        // Validate both classes have 8/8 active
        $primaryScenarios = MaritimeScenario::active()
            ->forClass($primaryClass)
            ->orderBy('slot')
            ->get();

        if ($primaryScenarios->count() < self::REQUIRED_SLOTS) {
            throw new ScenarioNotFoundException(
                commandClass: $primaryClass,
                required: self::REQUIRED_SLOTS,
                found: $primaryScenarios->count(),
            );
        }

        $secondaryScenarios = MaritimeScenario::active()
            ->forClass($secondaryClass)
            ->orderBy('slot')
            ->get();

        if ($secondaryScenarios->count() < self::REQUIRED_SLOTS) {
            throw new ScenarioNotFoundException(
                commandClass: $secondaryClass,
                required: self::REQUIRED_SLOTS,
                found: $secondaryScenarios->count(),
            );
        }

        // Take first N from primary, last M from secondary
        $selected = $primaryScenarios->take($primaryCount)
            ->merge($secondaryScenarios->skip($primaryCount)->take($secondaryCount));

        return $selected;
    }

    /**
     * Check if a command class has 8/8 active scenarios (no exception).
     */
    public function isReady(string $commandClass): bool
    {
        return MaritimeScenario::active()
            ->forClass($commandClass)
            ->count() >= self::REQUIRED_SLOTS;
    }
}

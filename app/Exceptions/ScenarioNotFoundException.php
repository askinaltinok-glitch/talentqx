<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ScenarioNotFoundException extends Exception
{
    public function __construct(
        public readonly string $commandClass,
        public readonly int $required = 8,
        public readonly int $found = 0,
        string $message = '',
    ) {
        $message = $message ?: "Scenario bank incomplete for class {$commandClass}: {$found}/{$required} active scenarios found.";
        parent::__construct($message, 422);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'scenario_bank_incomplete',
            'message' => $this->getMessage(),
            'command_class' => $this->commandClass,
            'required' => $this->required,
            'found' => $this->found,
        ], 422);
    }
}

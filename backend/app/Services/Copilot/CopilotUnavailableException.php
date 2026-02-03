<?php

namespace App\Services\Copilot;

use Exception;

/**
 * Exception thrown when Copilot service is unavailable.
 * This results in a 503 Service Unavailable response.
 */
class CopilotUnavailableException extends Exception
{
    public function __construct(string $message = 'Copilot service temporarily unavailable')
    {
        parent::__construct($message);
    }
}

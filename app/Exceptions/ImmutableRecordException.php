<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ImmutableRecordException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $recordId,
        public readonly array $lockedFields = [],
        int $code = 409
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'immutable_record',
            'message' => $this->getMessage(),
            'record_id' => $this->recordId,
            'locked_fields' => $this->lockedFields,
        ], 409);
    }
}

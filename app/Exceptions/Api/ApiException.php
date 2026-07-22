<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

abstract class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        string $message,
        private readonly ErrorCode $errorCode,
        private readonly int $httpStatus,
        private readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode->value,
        ];

        if ($this->details !== []) {
            $payload['details'] = $this->details;
        }

        return response()->json($payload, $this->httpStatus);
    }
}

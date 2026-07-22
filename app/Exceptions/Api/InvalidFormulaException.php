<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use App\Enums\ErrorCode;
use Throwable;

final class InvalidFormulaException extends ApiException
{
    public function __construct(string $reason, ?Throwable $previous = null)
    {
        parent::__construct(
            message: "Invalid formula: {$reason}",
            errorCode: ErrorCode::INVALID_FORMULA,
            httpStatus: 400,
            previous: $previous,
        );
    }
}

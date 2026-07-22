<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use App\Enums\ErrorCode;

final class ZeroConsumptionException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Total consumption for the specified period is zero; indexed price cannot be calculated.',
            errorCode: ErrorCode::ZERO_CONSUMPTION,
            httpStatus: 500,
        );
    }
}

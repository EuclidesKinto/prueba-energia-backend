<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use App\Enums\ErrorCode;

final class DataNotFoundException extends ApiException
{
    /**
     * @param  array<int, string>  $missingDates
     */
    public function __construct(array $missingDates)
    {
        parent::__construct(
            message: 'No consumption or price data found for the specified date range.',
            errorCode: ErrorCode::DATA_NOT_FOUND,
            httpStatus: 404,
            details: ['missing_dates' => $missingDates],
        );
    }
}

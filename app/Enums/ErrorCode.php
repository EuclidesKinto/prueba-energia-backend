<?php

declare(strict_types=1);

namespace App\Enums;

enum ErrorCode: string
{
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case INVALID_FORMULA = 'INVALID_FORMULA';
    case DATA_NOT_FOUND = 'DATA_NOT_FOUND';
    case ZERO_CONSUMPTION = 'ZERO_CONSUMPTION';
    case CALCULATION_ERROR = 'CALCULATION_ERROR';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
}

<?php

declare(strict_types=1);

namespace App\Services\IndexedPrice\DTOs;

use Carbon\CarbonImmutable;

final class CalculateResult
{
    public function __construct(
        public readonly float $priceIndexed,
        public readonly CarbonImmutable $startDate,
        public readonly CarbonImmutable $endDate,
        public readonly string $formula,
    ) {}
}

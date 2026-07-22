<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Consumption;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

interface ConsumptionRepositoryInterface
{
    /**
     * Returns consumptions between the given dates (inclusive), keyed by
     * date in "Y-m-d" format.
     *
     * @return array<string, Consumption>
     */
    public function getBetween(CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    /**
     * @return LengthAwarePaginator<int, Consumption>
     */
    public function paginate(?CarbonImmutable $startDate, ?CarbonImmutable $endDate, int $perPage): LengthAwarePaginator;
}

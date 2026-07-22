<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Price;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

interface PriceRepositoryInterface
{
    /**
     * Returns prices between the given dates (inclusive), keyed by date in
     * "Y-m-d" format.
     *
     * @return array<string, Price>
     */
    public function getBetween(CarbonImmutable $startDate, CarbonImmutable $endDate): array;

    /**
     * @return LengthAwarePaginator<int, Price>
     */
    public function paginate(?CarbonImmutable $startDate, ?CarbonImmutable $endDate, int $perPage): LengthAwarePaginator;
}

<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Price;
use App\Repositories\Contracts\PriceRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentPriceRepository implements PriceRepositoryInterface
{
    public function getBetween(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return Price::query()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(fn (Price $price) => $price->date->format('Y-m-d'))
            ->all();
    }

    public function paginate(?CarbonImmutable $startDate, ?CarbonImmutable $endDate, int $perPage): LengthAwarePaginator
    {
        $query = Price::query()->orderBy('date');

        if ($startDate !== null) {
            $query->where('date', '>=', $startDate->format('Y-m-d'));
        }

        if ($endDate !== null) {
            $query->where('date', '<=', $endDate->format('Y-m-d'));
        }

        return $query->paginate($perPage);
    }
}

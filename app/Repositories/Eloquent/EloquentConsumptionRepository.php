<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Consumption;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentConsumptionRepository implements ConsumptionRepositoryInterface
{
    public function getBetween(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return Consumption::query()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(fn (Consumption $consumption) => $consumption->date->format('Y-m-d'))
            ->all();
    }

    public function paginate(?CarbonImmutable $startDate, ?CarbonImmutable $endDate, int $perPage): LengthAwarePaginator
    {
        $query = Consumption::query()->orderBy('date');

        if ($startDate !== null) {
            $query->where('date', '>=', $startDate->format('Y-m-d'));
        }

        if ($endDate !== null) {
            $query->where('date', '<=', $endDate->format('Y-m-d'));
        }

        return $query->paginate($perPage);
    }
}

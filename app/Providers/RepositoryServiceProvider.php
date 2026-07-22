<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Repositories\Eloquent\EloquentConsumptionRepository;
use App\Repositories\Eloquent\EloquentPriceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsumptionRepositoryInterface::class, EloquentConsumptionRepository::class);
        $this->app->bind(PriceRepositoryInterface::class, EloquentPriceRepository::class);
    }
}

<?php

declare(strict_types=1);

use App\Models\Price;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Repositories\Eloquent\EloquentPriceRepository;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->repository = new EloquentPriceRepository;
});

it('is bound to the interface via the container', function () {
    expect(app(PriceRepositoryInterface::class))->toBeInstanceOf(EloquentPriceRepository::class);
});

it('returns prices indexed by date', function () {
    Price::factory()->create(['date' => '2025-03-01']);
    Price::factory()->create(['date' => '2025-03-02']);

    $result = $this->repository->getBetween(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-02'),
    );

    expect($result)->toHaveKeys(['2025-03-01', '2025-03-02'])
        ->and($result['2025-03-01'])->toBeInstanceOf(Price::class);
});

it('returns an empty array when there is no data in the range', function () {
    $result = $this->repository->getBetween(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-31'),
    );

    expect($result)->toBe([]);
});

it('filters correctly by date range', function () {
    Price::factory()->create(['date' => '2025-03-01']);
    Price::factory()->create(['date' => '2025-03-15']);
    Price::factory()->create(['date' => '2025-04-01']);

    $result = $this->repository->getBetween(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-31'),
    );

    expect($result)->toHaveCount(2)
        ->and($result)->toHaveKeys(['2025-03-01', '2025-03-15']);
});

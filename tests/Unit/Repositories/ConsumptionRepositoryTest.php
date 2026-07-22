<?php

declare(strict_types=1);

use App\Models\Consumption;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use App\Repositories\Eloquent\EloquentConsumptionRepository;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->repository = new EloquentConsumptionRepository;
});

it('is bound to the interface via the container', function () {
    expect(app(ConsumptionRepositoryInterface::class))->toBeInstanceOf(EloquentConsumptionRepository::class);
});

it('returns consumptions indexed by date', function () {
    Consumption::factory()->create(['date' => '2025-03-01']);
    Consumption::factory()->create(['date' => '2025-03-02']);

    $result = $this->repository->getBetween(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-02'),
    );

    expect($result)->toHaveKeys(['2025-03-01', '2025-03-02'])
        ->and($result['2025-03-01'])->toBeInstanceOf(Consumption::class);
});

it('returns an empty array when there is no data in the range', function () {
    $result = $this->repository->getBetween(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-31'),
    );

    expect($result)->toBe([]);
});

it('filters correctly by date range', function () {
    Consumption::factory()->create(['date' => '2025-03-01']);
    Consumption::factory()->create(['date' => '2025-03-15']);
    Consumption::factory()->create(['date' => '2025-04-01']);

    $result = $this->repository->getBetween(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-31'),
    );

    expect($result)->toHaveCount(2)
        ->and($result)->toHaveKeys(['2025-03-01', '2025-03-15']);
});

it('paginates and filters records by date range', function () {
    Consumption::factory()->count(3)->sequence(
        ['date' => '2025-03-01'],
        ['date' => '2025-03-02'],
        ['date' => '2025-04-01'],
    )->create();

    $paginator = $this->repository->paginate(
        CarbonImmutable::parse('2025-03-01'),
        CarbonImmutable::parse('2025-03-31'),
        10,
    );

    expect($paginator->total())->toBe(2);
});

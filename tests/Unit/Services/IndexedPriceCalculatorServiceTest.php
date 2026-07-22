<?php

declare(strict_types=1);

use App\Exceptions\Api\DataNotFoundException;
use App\Exceptions\Api\ZeroConsumptionException;
use App\Models\Consumption;
use App\Models\Price;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Services\Formula\FormulaEvaluator;
use App\Services\IndexedPrice\DTOs\CalculateInput;
use App\Services\IndexedPrice\IndexedPriceCalculatorService;
use Carbon\CarbonImmutable;

function calculatorService(array $consumptionsByDate, array $pricesByDate): IndexedPriceCalculatorService
{
    $consumptionRepo = Mockery::mock(ConsumptionRepositoryInterface::class);
    $priceRepo = Mockery::mock(PriceRepositoryInterface::class);

    $consumptionRepo->shouldReceive('getBetween')->andReturn($consumptionsByDate);
    $priceRepo->shouldReceive('getBetween')->andReturn($pricesByDate);

    return new IndexedPriceCalculatorService($consumptionRepo, $priceRepo, new FormulaEvaluator);
}

it('calculates correctly for 1 day, 1 hour', function () {
    $service = calculatorService(
        ['2025-03-01' => Consumption::factory()->make(['date' => '2025-03-01', 'h1' => 5.0])],
        ['2025-03-01' => Price::factory()->make(['date' => '2025-03-01', 'h1' => 0.10])],
    );

    $result = $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate: CarbonImmutable::parse('2025-03-01'),
        formula: '([OMIE_MD] * 0.6) + 0.88',
    ));

    expect($result->priceIndexed)->toBe(0.94);
});

it('calculates correctly for 1 day, 24 hours', function () {
    $consumptionHours = array_fill_keys(array_map(fn ($h) => "h{$h}", range(1, 24)), 2.0);
    $priceHours = array_fill_keys(array_map(fn ($h) => "h{$h}", range(1, 24)), 0.20);

    $service = calculatorService(
        ['2025-03-01' => Consumption::factory()->make(array_merge(['date' => '2025-03-01'], $consumptionHours))],
        ['2025-03-01' => Price::factory()->make(array_merge(['date' => '2025-03-01'], $priceHours))],
    );

    $result = $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate: CarbonImmutable::parse('2025-03-01'),
        formula: '[OMIE_MD]',
    ));

    // Constant consumption and price across every hour: the weighted average is the price itself.
    expect($result->priceIndexed)->toBe(0.2);
});

it('treats NULL as zero and ignores those hours in the calculation', function () {
    $service = calculatorService(
        ['2025-03-01' => Consumption::factory()->make([
            'date' => '2025-03-01', 'h1' => 5.0, 'h2' => 3.0, 'h3' => 2.0,
        ])],
        ['2025-03-01' => Price::factory()->make([
            'date' => '2025-03-01', 'h1' => 0.10, 'h2' => 0.20, 'h3' => 0.15,
        ])],
    );

    $result = $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate: CarbonImmutable::parse('2025-03-01'),
        formula: '([OMIE_MD] * 0.6) + 0.88',
    ));

    // h1: (0.10*0.6+0.88)*5=4.70 | h2: (0.20*0.6+0.88)*3=3.00 | h3: (0.15*0.6+0.88)*2=1.94
    // total_amounts=9.64 total_consumption=10 -> 0.964
    expect($result->priceIndexed)->toBe(0.964);
});

it('ignores hours with zero consumption even when the price is invalid for the formula', function () {
    $service = calculatorService(
        ['2025-03-01' => Consumption::factory()->make(['date' => '2025-03-01', 'h1' => 0.0, 'h2' => 4.0])],
        ['2025-03-01' => Price::factory()->make(['date' => '2025-03-01', 'h1' => 999.0, 'h2' => 0.25])],
    );

    $result = $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate: CarbonImmutable::parse('2025-03-01'),
        formula: '[OMIE_MD]',
    ));

    expect($result->priceIndexed)->toBe(0.25);
});

it('throws DataNotFoundException when a day in the range is missing', function () {
    $service = calculatorService(
        ['2025-03-01' => Consumption::factory()->make(['date' => '2025-03-01', 'h1' => 5.0])],
        ['2025-03-01' => Price::factory()->make(['date' => '2025-03-01', 'h1' => 0.10])],
        // 2025-03-02 is missing from both repositories
    );

    $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate: CarbonImmutable::parse('2025-03-02'),
        formula: '[OMIE_MD]',
    ));
})->throws(DataNotFoundException::class);

it('throws ZeroConsumptionException when total consumption is zero', function () {
    $service = calculatorService(
        ['2025-03-01' => Consumption::factory()->make(['date' => '2025-03-01', 'h1' => 0.0])],
        ['2025-03-01' => Price::factory()->make(['date' => '2025-03-01', 'h1' => 0.10])],
    );

    $service->calculate(new CalculateInput(
        startDate: CarbonImmutable::parse('2025-03-01'),
        endDate: CarbonImmutable::parse('2025-03-01'),
        formula: '[OMIE_MD]',
    ));
})->throws(ZeroConsumptionException::class);

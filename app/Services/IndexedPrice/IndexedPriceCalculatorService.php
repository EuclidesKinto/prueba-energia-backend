<?php

declare(strict_types=1);

namespace App\Services\IndexedPrice;

use App\Exceptions\Api\DataNotFoundException;
use App\Exceptions\Api\ZeroConsumptionException;
use App\Models\Consumption;
use App\Models\Price;
use App\Repositories\Contracts\ConsumptionRepositoryInterface;
use App\Repositories\Contracts\PriceRepositoryInterface;
use App\Services\Formula\FormulaEvaluator;
use App\Services\IndexedPrice\DTOs\CalculateInput;
use App\Services\IndexedPrice\DTOs\CalculateResult;

final class IndexedPriceCalculatorService
{
    private const HOURS = 25;

    public function __construct(
        private readonly ConsumptionRepositoryInterface $consumptions,
        private readonly PriceRepositoryInterface $prices,
        private readonly FormulaEvaluator $evaluator,
    ) {}

    public function calculate(CalculateInput $input): CalculateResult
    {
        $consumptions = $this->consumptions->getBetween($input->startDate, $input->endDate);
        $prices = $this->prices->getBetween($input->startDate, $input->endDate);

        $this->assertAllDatesPresent($input, $consumptions, $prices);

        $sumImportes = 0.0;
        $sumConsumos = 0.0;

        foreach ($consumptions as $date => $consumption) {
            $consumptionHours = $consumption->hoursArray();
            $priceHours = $prices[$date]->hoursArray();

            for ($h = 1; $h <= self::HOURS; $h++) {
                $consumoHora = (float) ($consumptionHours["h{$h}"] ?? 0);
                $precioHora = (float) ($priceHours["h{$h}"] ?? 0);

                if ($consumoHora === 0.0) {
                    continue;
                }

                $importeHora = $this->evaluator->evaluate($input->formula, $precioHora) * $consumoHora;

                $sumImportes += $importeHora;
                $sumConsumos += $consumoHora;
            }
        }

        if ($sumConsumos === 0.0) {
            throw new ZeroConsumptionException;
        }

        return new CalculateResult(
            priceIndexed: round($sumImportes / $sumConsumos, 4),
            startDate: $input->startDate,
            endDate: $input->endDate,
            formula: $input->formula,
        );
    }

    /**
     * @param  array<string, Consumption>  $consumptions
     * @param  array<string, Price>  $prices
     */
    private function assertAllDatesPresent(CalculateInput $input, array $consumptions, array $prices): void
    {
        $missing = [];

        for ($date = $input->startDate; $date->lte($input->endDate); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');

            if (! isset($consumptions[$key]) || ! isset($prices[$key])) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new DataNotFoundException($missing);
        }
    }
}

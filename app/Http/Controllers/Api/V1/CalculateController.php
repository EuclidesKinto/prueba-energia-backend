<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CalculateRequest;
use App\Http\Resources\V1\CalculateResource;
use App\Services\IndexedPrice\DTOs\CalculateInput;
use App\Services\IndexedPrice\IndexedPriceCalculatorService;
use Illuminate\Http\JsonResponse;

final class CalculateController extends Controller
{
    public function __construct(
        private readonly IndexedPriceCalculatorService $calculator,
    ) {}

    public function store(CalculateRequest $request): JsonResponse
    {
        $input = new CalculateInput(
            startDate: $request->getStartDate(),
            endDate: $request->getEndDate(),
            formula: $request->getFormula(),
        );

        $result = $this->calculator->calculate($input);

        return CalculateResource::make($result)
            ->response()
            ->setStatusCode(200);
    }
}

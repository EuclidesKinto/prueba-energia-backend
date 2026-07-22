<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Services\IndexedPrice\DTOs\CalculateResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CalculateResult $resource
 */
final class CalculateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'price_indexed' => $this->resource->priceIndexed,
            'start_date' => $this->resource->startDate->format('Y-m-d'),
            'end_date' => $this->resource->endDate->format('Y-m-d'),
            'formula' => $this->resource->formula,
        ];
    }
}

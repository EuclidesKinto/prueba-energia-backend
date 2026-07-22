<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Price $resource
 */
final class PriceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'date' => $this->resource->date->format('Y-m-d'),
            'hours' => $this->resource->hoursArray(),
        ];
    }
}

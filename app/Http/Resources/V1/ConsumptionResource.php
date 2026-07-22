<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Consumption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Consumption $resource
 */
final class ConsumptionResource extends JsonResource
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

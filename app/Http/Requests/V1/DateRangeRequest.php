<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class DateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function getStartDate(): ?CarbonImmutable
    {
        $value = $this->string('start_date')->value();

        return $value ? CarbonImmutable::parse($value)->startOfDay() : null;
    }

    public function getEndDate(): ?CarbonImmutable
    {
        $value = $this->string('end_date')->value();

        return $value ? CarbonImmutable::parse($value)->startOfDay() : null;
    }

    public function getPerPage(): int
    {
        return $this->filled('per_page') ? $this->integer('per_page') : 30;
    }
}

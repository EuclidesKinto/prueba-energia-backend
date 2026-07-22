<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Rules\ValidFormula;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

final class CalculateRequest extends FormRequest
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'formula' => ['required', 'string', 'min:1', 'max:500', new ValidFormula],
        ];
    }

    public function getStartDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('start_date')->value())->startOfDay();
    }

    public function getEndDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('end_date')->value())->startOfDay();
    }

    public function getFormula(): string
    {
        return $this->string('formula')->value();
    }
}

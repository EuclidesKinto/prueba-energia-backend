<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Formula\FormulaValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidFormula implements ValidationRule
{
    public function __construct(
        private readonly FormulaValidator $validator = new FormulaValidator,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute field must be a string.');

            return;
        }

        foreach ($this->validator->validate($value) as $error) {
            $fail($error);
        }
    }
}

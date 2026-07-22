<?php

declare(strict_types=1);

namespace App\Services\Formula;

use App\Exceptions\Api\InvalidFormulaException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

final class FormulaEvaluator
{
    private const PLACEHOLDER = '[OMIE_MD]';

    private const VARIABLE = 'omie_md';

    public function __construct(
        private readonly ExpressionLanguage $expression = new ExpressionLanguage,
    ) {}

    public function evaluate(string $formula, float $omieMdValue): float
    {
        $normalized = str_replace(self::PLACEHOLDER, self::VARIABLE, $formula);

        try {
            $result = $this->expression->evaluate($normalized, [
                self::VARIABLE => $omieMdValue,
            ]);
        } catch (SyntaxError $e) {
            throw new InvalidFormulaException($e->getMessage(), $e);
        }

        if (! is_numeric($result)) {
            throw new InvalidFormulaException('formula did not evaluate to a numeric value.');
        }

        return (float) $result;
    }
}

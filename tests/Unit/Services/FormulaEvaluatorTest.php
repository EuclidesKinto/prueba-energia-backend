<?php

declare(strict_types=1);

use App\Exceptions\Api\InvalidFormulaException;
use App\Services\Formula\FormulaEvaluator;

it('evaluates a simple formula with a single number', function () {
    $evaluator = new FormulaEvaluator;

    expect($evaluator->evaluate('[OMIE_MD]', 0.15))->toBe(0.15);
});

it('evaluates a formula with multiple operations', function () {
    $evaluator = new FormulaEvaluator;

    expect($evaluator->evaluate('([OMIE_MD] * 0.6) + 0.88', 0.10))->toBe(0.94);
});

it('resolves operator precedence correctly', function () {
    $evaluator = new FormulaEvaluator;

    // 2 + 3 * 4 = 14, not 20
    expect($evaluator->evaluate('[OMIE_MD] + 3 * 4', 2))->toBe(14.0);
});

it('substitutes every occurrence of [OMIE_MD]', function () {
    $evaluator = new FormulaEvaluator;

    expect($evaluator->evaluate('[OMIE_MD] + [OMIE_MD]', 0.5))->toBe(1.0);
});

it('throws InvalidFormulaException for invalid syntax', function () {
    $evaluator = new FormulaEvaluator;

    $evaluator->evaluate('([OMIE_MD] * 0.6', 0.10);
})->throws(InvalidFormulaException::class);

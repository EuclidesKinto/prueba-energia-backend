<?php

declare(strict_types=1);

use App\Services\Formula\FormulaValidator;

it('detects the absence of the [OMIE_MD] token', function () {
    $validator = new FormulaValidator;

    expect($validator->containsToken('0.5 + 0.1'))->toBeFalse()
        ->and($validator->validate('0.5 + 0.1'))
        ->toContain('The formula field must contain the [OMIE_MD] segment.');
});

it('accepts a formula that contains the [OMIE_MD] token', function () {
    $validator = new FormulaValidator;

    expect($validator->containsToken('([OMIE_MD] * 0.6) + 0.88'))->toBeTrue();
});

it('detects disallowed characters', function () {
    $validator = new FormulaValidator;

    expect($validator->hasValidCharacters('[OMIE_MD] * 0.6; DROP TABLE users;'))->toBeFalse();
});

it('considers a formula valid when it only uses allowed operators', function () {
    $validator = new FormulaValidator;

    expect($validator->validate('([OMIE_MD] * 0.6) + 0.88'))->toBe([]);
});

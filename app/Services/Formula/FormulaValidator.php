<?php

declare(strict_types=1);

namespace App\Services\Formula;

final class FormulaValidator
{
    private const TOKEN = '[OMIE_MD]';

    private const ALLOWED_CHARACTERS_PATTERN = '/^[0-9+\-*\/(). \[\]A-Z_]+$/';

    public function containsToken(string $formula): bool
    {
        return str_contains($formula, self::TOKEN);
    }

    public function hasValidCharacters(string $formula): bool
    {
        return (bool) preg_match(self::ALLOWED_CHARACTERS_PATTERN, $formula);
    }

    /**
     * @return array<int, string>
     */
    public function validate(string $formula): array
    {
        $errors = [];

        if (! $this->containsToken($formula)) {
            $errors[] = 'The formula field must contain the [OMIE_MD] segment.';
        }

        if (! $this->hasValidCharacters($formula)) {
            $errors[] = 'The formula field contains characters that are not allowed.';
        }

        return $errors;
    }
}

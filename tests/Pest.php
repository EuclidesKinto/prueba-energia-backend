<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

expect()->extend('toBeApproximately', function (float $expected, float $tolerance = 0.0001) {
    return $this->toBeGreaterThanOrEqual($expected - $tolerance)
        ->toBeLessThanOrEqual($expected + $tolerance);
});

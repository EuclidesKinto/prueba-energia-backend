<?php

declare(strict_types=1);

use App\Models\Consumption;
use App\Models\Price;

it('calculates the indexed price correctly with complete data', function () {
    Consumption::factory()->create(['date' => '2025-03-01', 'h1' => 5.0, 'h2' => 3.0, 'h3' => 2.0]);
    Price::factory()->create(['date' => '2025-03-01', 'h1' => 0.10, 'h2' => 0.20, 'h3' => 0.15]);

    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-01',
        'formula' => '([OMIE_MD] * 0.6) + 0.88',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.price_indexed', 0.964)
        ->assertJsonPath('data.start_date', '2025-03-01')
        ->assertJsonPath('data.end_date', '2025-03-01');
});

it('calculates correctly via the POST /calculate compatibility alias', function () {
    Consumption::factory()->create(['date' => '2025-03-01', 'h1' => 1.0]);
    Price::factory()->create(['date' => '2025-03-01', 'h1' => 0.10]);

    $response = $this->postJson('/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-01',
        'formula' => '[OMIE_MD]',
    ]);

    $response->assertStatus(200)->assertJsonPath('data.price_indexed', 0.1);
});

it('returns 400 when start_date is missing', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'end_date' => '2025-03-31',
        'formula' => '([OMIE_MD] * 0.6) + 0.88',
    ]);

    $response->assertStatus(400)->assertJsonValidationErrors(['start_date']);
});

it('returns 400 when end_date is missing', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'formula' => '([OMIE_MD] * 0.6) + 0.88',
    ]);

    $response->assertStatus(400)->assertJsonValidationErrors(['end_date']);
});

it('returns 400 when formula is missing', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-31',
    ]);

    $response->assertStatus(400)->assertJsonValidationErrors(['formula']);
});

it('returns 400 when formula does not contain [OMIE_MD]', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-31',
        'formula' => '0.5 + 0.1',
    ]);

    $response->assertStatus(400)
        ->assertJsonPath('errors.formula.0', 'The formula field must contain the [OMIE_MD] segment.');
});

it('returns 400 when end_date is before start_date', function () {
    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-31',
        'end_date' => '2025-03-01',
        'formula' => '([OMIE_MD] * 0.6) + 0.88',
    ]);

    $response->assertStatus(400)->assertJsonValidationErrors(['end_date']);
});

it('returns 400 when the formula has invalid syntax', function () {
    Consumption::factory()->create(['date' => '2025-03-01', 'h1' => 1.0]);
    Price::factory()->create(['date' => '2025-03-01', 'h1' => 0.10]);

    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-01',
        'formula' => '([OMIE_MD] * 0.6',
    ]);

    $response->assertStatus(400)->assertJsonPath('error_code', 'INVALID_FORMULA');
});

it('returns 404 when days in the range are missing', function () {
    Consumption::factory()->create(['date' => '2025-03-01', 'h1' => 1.0]);
    Price::factory()->create(['date' => '2025-03-01', 'h1' => 0.10]);
    // 2025-03-02 is missing from both tables

    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-02',
        'formula' => '[OMIE_MD]',
    ]);

    $response->assertStatus(404)
        ->assertJsonPath('error_code', 'DATA_NOT_FOUND')
        ->assertJsonPath('details.missing_dates', ['2025-03-02']);
});

it('returns 500 when total consumption for the period is zero', function () {
    Consumption::factory()->create(['date' => '2025-03-01', 'h1' => 0.0]);
    Price::factory()->create(['date' => '2025-03-01', 'h1' => 0.10]);

    $response = $this->postJson('/api/v1/calculate', [
        'start_date' => '2025-03-01',
        'end_date' => '2025-03-01',
        'formula' => '[OMIE_MD]',
    ]);

    $response->assertStatus(500)->assertJsonPath('error_code', 'ZERO_CONSUMPTION');
});

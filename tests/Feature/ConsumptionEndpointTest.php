<?php

declare(strict_types=1);

use App\Models\Consumption;

it('lists paginated consumptions', function () {
    Consumption::factory()->count(35)->sequence(
        ...collect(range(1, 35))->map(fn (int $day) => ['date' => sprintf('2025-01-%02d', $day)])->all(),
    )->create();

    $response = $this->getJson('/api/v1/consumptions?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 35)
        ->assertJsonPath('meta.per_page', 10);
});

it('filters consumptions by date range', function () {
    Consumption::factory()->create(['date' => '2025-03-01']);
    Consumption::factory()->create(['date' => '2025-03-05']);
    Consumption::factory()->create(['date' => '2025-04-01']);

    $response = $this->getJson('/api/v1/consumptions?start_date=2025-03-01&end_date=2025-03-07');

    $response->assertStatus(200)->assertJsonCount(2, 'data');
});

it('returns an empty list when there are no consumptions', function () {
    $response = $this->getJson('/api/v1/consumptions');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

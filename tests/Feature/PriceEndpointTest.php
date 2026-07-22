<?php

declare(strict_types=1);

use App\Models\Price;

it('lists paginated prices', function () {
    Price::factory()->count(35)->sequence(
        ...collect(range(1, 35))->map(fn (int $day) => ['date' => sprintf('2025-01-%02d', $day)])->all(),
    )->create();

    $response = $this->getJson('/api/v1/prices?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 35)
        ->assertJsonPath('meta.per_page', 10);
});

it('filters prices by date range', function () {
    Price::factory()->create(['date' => '2025-03-01']);
    Price::factory()->create(['date' => '2025-03-05']);
    Price::factory()->create(['date' => '2025-04-01']);

    $response = $this->getJson('/api/v1/prices?start_date=2025-03-01&end_date=2025-03-07');

    $response->assertStatus(200)->assertJsonCount(2, 'data');
});

it('returns an empty list when there are no prices', function () {
    $response = $this->getJson('/api/v1/prices');

    $response->assertStatus(200)->assertJsonCount(0, 'data');
});

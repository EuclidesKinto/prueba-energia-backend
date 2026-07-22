<?php

declare(strict_types=1);

it('returns ok status and a healthy database connection', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('database', 'ok')
        ->assertJsonStructure(['status', 'timestamp', 'database']);
});

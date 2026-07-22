<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    protected $model = Price::class;

    /**
     * All hourly slots default to null so tests can set only the hours
     * relevant to the scenario being asserted. Use withRandomHours() for
     * realistic, fully populated days (e.g. seeders).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(
            ['date' => fake()->unique()->date()],
            array_fill_keys($this->hourKeys(), null),
        );
    }

    public function withRandomHours(): static
    {
        return $this->state(function () {
            $hours = [];

            for ($h = 1; $h <= 24; $h++) {
                $hours["h{$h}"] = fake()->randomFloat(4, 0.05, 0.35);
            }

            return $hours;
        });
    }

    public function withFallBackHour(): static
    {
        return $this->state(fn (array $attributes) => [
            'h25' => fake()->randomFloat(4, 0.05, 0.35),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function hourKeys(): array
    {
        return array_map(fn (int $h) => "h{$h}", range(1, 25));
    }
}

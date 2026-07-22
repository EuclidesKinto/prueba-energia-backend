<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Price;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PriceSeeder extends Seeder
{
    public function run(): void
    {
        $start = CarbonImmutable::create(2025, 3, 1);
        $end = CarbonImmutable::create(2025, 3, 31);

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            Price::factory()->withRandomHours()->create(['date' => $date->format('Y-m-d')]);
        }
    }
}

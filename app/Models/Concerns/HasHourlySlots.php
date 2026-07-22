<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasHourlySlots
{
    public const HOURS = 25;

    /**
     * @return array<string, float|null>
     */
    public function hoursArray(): array
    {
        $hours = [];

        for ($h = 1; $h <= self::HOURS; $h++) {
            $hours["h{$h}"] = $this->{"h{$h}"};
        }

        return $hours;
    }
}

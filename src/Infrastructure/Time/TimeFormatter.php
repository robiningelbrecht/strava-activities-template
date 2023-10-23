<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use Carbon\CarbonInterval;

trait TimeFormatter
{
    public function formatDurationForHumans(int $timeInSeconds): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        $movingTime = implode(':', array_filter(array_map(fn (int $value) => sprintf('%02d', $value), [
            $interval->minutes,
            $interval->seconds,
        ])));

        if ($hours = $interval->hours) {
            $movingTime = $hours.':'.$movingTime;
        }

        return ltrim($movingTime, '0');
    }
}

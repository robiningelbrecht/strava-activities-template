<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use Carbon\CarbonInterval;

trait TimeFormatter
{
    public function formatDurationForHumans(int $timeInSeconds, bool $trimLeadingZeros = true): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        if (!$trimLeadingZeros) {
            return implode(':', array_map(fn (int $value) => sprintf('%02d', $value), [
                $interval->hours,
                $interval->minutes,
                $interval->seconds,
            ]));
        }

        $movingTime = implode(':', array_map(fn (int $value) => sprintf('%02d', $value), [
            $interval->minutes,
            $interval->seconds,
        ]));

        if ($hours = $interval->hours) {
            $movingTime = $hours.':'.$movingTime;
        }

        return ltrim($movingTime, '0');
    }
}

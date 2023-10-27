<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

use Carbon\CarbonInterval;

trait TimeFormatter
{
    public function formatDurationForHumans(int $timeInSeconds, bool $trimLeadingZeros = true): string
    {
        $interval = CarbonInterval::seconds($timeInSeconds)->cascade();

        $values = [
            $interval->hours,
            $interval->minutes,
            $interval->seconds,
        ];

        if ($trimLeadingZeros) {
            $values = array_filter($values);
        }

        $movingTime = implode(':', array_map(fn (int $value) => sprintf('%02d', $value), $values));

        if (!$trimLeadingZeros) {
            return $movingTime;
        }

        return ltrim($movingTime, '0');
    }
}

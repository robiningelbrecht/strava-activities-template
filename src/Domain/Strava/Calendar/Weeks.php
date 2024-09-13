<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @extends Collection<Week>
 */
final class Weeks extends Collection
{
    public function getItemClassName(): string
    {
        return Week::class;
    }

    public static function create(
        SerializableDateTime $startDate,
        SerializableDateTime $now,
    ): self {
        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1W'),
            $now
        );

        $weeks = [];
        foreach ($period as $date) {
            $date = SerializableDateTime::fromDateTimeImmutable($date);
            $week = Week::fromYearAndWeekNumber(
                year: $date->getYearAndWeekNumber()[0],
                weekNumber: $date->getYearAndWeekNumber()[1]
            );

            $weeks[$week->getId()] = $week;
        }

        return Weeks::fromArray(array_values($weeks));
    }
}

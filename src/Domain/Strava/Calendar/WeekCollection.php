<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

/**
 * @extends Collection<Week>
 */
final class WeekCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Week::class;
    }

    public static function create(
        SerializableDateTime $startDate,
        SerializableDateTime $now
    ): self {
        $weeks = WeekCollection::empty();

        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1W'),
            $now
        );

        foreach ($period as $date) {
            $weeks->add(Week::fromDate(SerializableDateTime::fromDateTimeImmutable($date)));
        }

        return $weeks;
    }
}

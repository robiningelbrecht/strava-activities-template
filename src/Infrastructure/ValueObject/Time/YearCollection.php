<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Time;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Year>
 */
class YearCollection extends Collection
{
    public function getItemClassName(): string
    {
        return Year::class;
    }

    public static function create(
        SerializableDateTime $startDate,
        SerializableDateTime $endDate
    ): self {
        $years = YearCollection::empty();
        $period = new \DatePeriod(
            $startDate->modify('first day of january this year'),
            new \DateInterval('P1Y'),
            $endDate->modify('last day of december this year')
        );

        foreach ($period as $date) {
            $years->add(Year::fromDate(SerializableDateTime::fromDateTimeImmutable($date)));
        }

        return $years;
    }
}

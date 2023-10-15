<?php

namespace App\Infrastructure\ValueObject\Time;

final class DateCollection
{
    /** @var \App\Infrastructure\ValueObject\Time\SerializableDateTime[] */
    private array $datesIndexedByDate;

    private function __construct(array $dates)
    {
        $this->datesIndexedByDate = [];
        foreach ($dates as $date) {
            if (!$date instanceof SerializableDateTime) {
                throw new \InvalidArgumentException('Invalid SerializableDateTime');
            }
            $this->datesIndexedByDate[$date->format('Y-m-d')] = $date;
        }
        \ksort($this->datesIndexedByDate);
    }

    public static function fromDates(array $dates): self
    {
        return new self($dates);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function countMostConsecutiveDates(): int
    {
        if (0 === count($this->datesIndexedByDate)) {
            return 0;
        }

        $keys = \array_keys($this->datesIndexedByDate);

        $delta = 0;
        $mostConsecutiveDayCount = 0;
        $currentConsecutiveDayCount = 0;
        foreach ($this->datesIndexedByDate as $date) {
            if ($delta > 0 && $date->modify('-1 day')->format('Y-m-d') !== $this->datesIndexedByDate[$keys[$delta - 1]]->format('Y-m-d')) {
                // Date is not consecutive.
                $currentConsecutiveDayCount = 0;
            }
            ++$currentConsecutiveDayCount;
            ++$delta;
            if ($currentConsecutiveDayCount > $mostConsecutiveDayCount) {
                $mostConsecutiveDayCount = $currentConsecutiveDayCount;
            }
        }

        return $mostConsecutiveDayCount;
    }
}

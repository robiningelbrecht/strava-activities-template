<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Month
{
    public const MONTH_ID_FORMAT = 'Y-m';
    private SerializableDateTime $firstDay;

    private function __construct(
        private int $year,
        private int $month,
    ) {
        $this->firstDay = SerializableDateTime::createFromFormat(
            format: 'd-n-Y',
            datetime: '01-'.$this->month.'-'.$this->year,
        );
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public static function fromDate(SerializableDateTime $date): self
    {
        return new self(
            year: (int) $date->format('Y'),
            month: (int) $date->format('n'),
        );
    }

    public function getLabel(): string
    {
        return $this->firstDay->format('F Y');
    }

    public function getId(): string
    {
        return $this->firstDay->format(self::MONTH_ID_FORMAT);
    }

    public function getNumberOfDays(): int
    {
        return (int) $this->firstDay->format('t');
    }

    public function getWeekDayOfFirstDay(): int
    {
        // Numeric representation of week day, 1 (for Monday) through 7 (for Sunday)
        return (int) $this->firstDay->format('N');
    }

    public function getPreviousMonth(): self
    {
        $date = $this->firstDay->modify('first day of previous month');

        return self::fromDate($date);
    }

    public function getNextMonth(): self
    {
        $date = $this->firstDay->modify('first day of next month');

        return self::fromDate($date);
    }
}

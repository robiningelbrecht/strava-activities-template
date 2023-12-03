<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Week
{
    public const WEEK_ID_FORMAT = 'Y-W';

    private function __construct(
        private SerializableDateTime $date,
    ) {
    }

    public static function fromDate(SerializableDateTime $date): self
    {
        return new self(
            date: $date,
        );
    }

    public function getId(): string
    {
        return $this->date->format(self::WEEK_ID_FORMAT);
    }

    public function getLabel(): string
    {
        return $this->date->format('M Y');
    }

    public function getNextWeek(): Week
    {
        return Week::fromDate($this->date->modify('next monday'));
    }
}

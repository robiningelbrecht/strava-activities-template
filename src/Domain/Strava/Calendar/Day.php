<?php

declare(strict_types=1);

namespace App\Domain\Strava\Calendar;

use App\Domain\Strava\Activity\ActivityCollection;

final readonly class Day
{
    private function __construct(
        private int $dayNumber,
        private bool $isCurrentMonth,
        private ActivityCollection $activities,
    ) {
    }

    public static function create(
        int $dayNumber,
        bool $isCurrentMonth,
        ActivityCollection $activities,
    ): self {
        return new self(
            dayNumber: $dayNumber,
            isCurrentMonth: $isCurrentMonth,
            activities: $activities
        );
    }

    public function getDayNumber(): int
    {
        return $this->dayNumber;
    }

    public function isCurrentMonth(): bool
    {
        return $this->isCurrentMonth;
    }

    public function getActivities(): ActivityCollection
    {
        return $this->activities;
    }
}

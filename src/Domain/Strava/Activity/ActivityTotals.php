<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

final class ActivityTotals
{
    private SerializableDateTime $startDate;

    private function __construct(
        private readonly ActivityCollection $activities,
        private readonly SerializableDateTime $now,
    ) {
        $this->startDate = new SerializableDateTime();
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->isAfterOrOn($this->startDate)) {
                continue;
            }
            $this->startDate = $activity->getStartDate();
        }
    }

    public function getDistance(): float
    {
        return array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $this->activities->toArray()));
    }

    public function getElevation(): int
    {
        return array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $this->activities->toArray()));
    }

    public function getCalories(): int
    {
        return array_sum(array_map(fn (Activity $activity) => $activity->getCalories(), $this->activities->toArray()));
    }

    public function getMovingTimeFormatted(): string
    {
        $seconds = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $this->activities->toArray()));

        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
    }

    public function getStartDate(): SerializableDateTime
    {
        return $this->startDate;
    }

    public function getDailyAverage(): float
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return 0;
        }

        return $this->getDistance() / $diff->days;
    }

    public function getWeeklyAverage(): float
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return 0;
        }

        return $this->getDistance() / ceil($diff->days / 7);
    }

    public function getMonthlyAverage(): float
    {
        $diff = $this->getStartDate()->diff($this->now);
        if (0 === $diff->days) {
            return 0;
        }

        return $this->getDistance() / (($diff->y * 12) + $diff->m + 1);
    }

    public function getTotalDays(): int
    {
        return (int) $this->now->diff($this->startDate)->days;
    }

    public function getTotalDaysOfCycling(): int
    {
        return count(array_unique(array_map(fn (Activity $activity) => $activity->getStartDate()->format('Ymd'), $this->activities->toArray())));
    }

    public static function fromActivities(ActivityCollection $activities, SerializableDateTime $now): self
    {
        return new self($activities, $now);
    }
}

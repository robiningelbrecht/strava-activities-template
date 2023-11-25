<?php

namespace App\Domain\Strava\Activity;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

final readonly class ActivityTotals
{
    private function __construct(
        private ActivityCollection $activities,
        private SerializableDateTime $now,
    ) {
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
        return $this->activities->getFirstActivityStartDate();
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

    public function getTotalDaysSinceFirstActivity(): string
    {
        $days = (int) $this->now->diff($this->getStartDate())->days;

        return CarbonInterval::days($days)->cascade()->forHumans(['minimumUnit' => 'day', 'join' => [' ', ' and '], 'parts' => 2]);
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

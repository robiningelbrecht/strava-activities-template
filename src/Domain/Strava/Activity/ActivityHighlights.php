<?php

namespace App\Domain\Strava\Activity;

final readonly class ActivityHighlights
{
    private function __construct(
        /** @var \App\Domain\Strava\Activity\Activity[] */
        private array $activities,
    ) {
    }

    public function getLongestDistance(): float
    {
        return max(array_map(fn (Activity $activity) => $activity->getDistance(), $this->activities));
    }

    public function getHighestElevation(): int
    {
        return max(array_map(fn (Activity $activity) => $activity->getElevation(), $this->activities));
    }

    public function getHighestAveragePower(): int
    {
        return max(array_map(fn (Activity $activity) => $activity->getAveragePower(), $this->activities));
    }

    public function getFastestAverageSpeed(): float
    {
        return max(array_map(fn (Activity $activity) => $activity->getAverageSpeedInKmPerH(), $this->activities));
    }

    public function getHighestAverageHeartRate(): int
    {
        return max(array_map(fn (Activity $activity) => $activity->getAverageHeartRate(), $this->activities));
    }

    public function getLongestMovingTimeFormatted(): string
    {
        $activityWithMaxMovingTime = null;
        foreach ($this->activities as $activity) {
            if ($activity->getMovingTime() < $activityWithMaxMovingTime?->getMovingTime()) {
                continue;
            }
            $activityWithMaxMovingTime = $activity;
        }

        return $activityWithMaxMovingTime->getMovingTimeFormatted();
    }

    public static function fromActivities(array $activities): self
    {
        return new self($activities);
    }
}

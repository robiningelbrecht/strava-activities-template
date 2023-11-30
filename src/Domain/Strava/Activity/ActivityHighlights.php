<?php

namespace App\Domain\Strava\Activity;

final readonly class ActivityHighlights
{
    private function __construct(
        private ActivityCollection $activities,
    ) {
    }

    public function getLongestDistance(): float
    {
        return max(array_map(fn (Activity $activity) => $activity->getDistance(), $this->activities->toArray()));
    }

    public function getHighestElevation(): int
    {
        return max(array_map(fn (Activity $activity) => $activity->getElevation(), $this->activities->toArray()));
    }

    public function getHighestAveragePower(): int
    {
        return (int) max(array_map(fn (Activity $activity) => $activity->getAveragePower(), $this->activities->toArray()));
    }

    public function getFastestAverageSpeed(): float
    {
        return max(array_map(fn (Activity $activity) => $activity->getAverageSpeedInKmPerH(), $this->activities->toArray()));
    }

    public function getHighestAverageHeartRate(): int
    {
        return (int) max(array_map(fn (Activity $activity) => $activity->getAverageHeartRate(), $this->activities->toArray()));
    }

    public function getCalories(): int
    {
        return (int) max(array_map(fn (Activity $activity) => $activity->getCalories(), $this->activities->toArray()));
    }

    public function getLongestMovingTimeFormatted(): ?string
    {
        $activityWithMaxMovingTime = null;
        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($this->activities as $activity) {
            if ($activity->getMovingTimeInSeconds() < $activityWithMaxMovingTime?->getMovingTimeInSeconds()) {
                continue;
            }
            $activityWithMaxMovingTime = $activity;
        }

        if (!$activityWithMaxMovingTime) {
            return null;
        }

        return $activityWithMaxMovingTime->getMovingTimeFormatted();
    }

    public static function fromActivities(ActivityCollection $activities): self
    {
        return new self($activities);
    }
}

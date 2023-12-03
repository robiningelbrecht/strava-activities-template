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
        return $this->activities->max(fn (Activity $activity) => $activity->getDistanceInKilometer());
    }

    public function getHighestElevation(): float
    {
        return $this->activities->max(fn (Activity $activity) => $activity->getElevationInMeter());
    }

    public function getHighestAveragePower(): int
    {
        return (int) $this->activities->max(fn (Activity $activity) => $activity->getAveragePower() ?? 0);
    }

    public function getFastestAverageSpeed(): float
    {
        return $this->activities->max(fn (Activity $activity) => $activity->getAverageSpeedInKmPerH());
    }

    public function getHighestAverageHeartRate(): int
    {
        return (int) $this->activities->max(fn (Activity $activity) => $activity->getAverageHeartRate() ?? 0);
    }

    public function getCalories(): int
    {
        return (int) $this->activities->max(fn (Activity $activity) => $activity->getCalories());
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

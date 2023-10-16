<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Infrastructure\ValueObject\Time\DateCollection;

final readonly class Trivia
{
    /**
     * @param \App\Domain\Strava\Activity\Activity[] $activities
     */
    private function __construct(
        private array $activities,
    ) {
    }

    /**
     * @param \App\Domain\Strava\Activity\Activity[] $activities
     */
    public static function fromActivities(array $activities): self
    {
        return new self($activities);
    }

    public function getTotalKudosReceived(): int
    {
        return array_sum(array_map(fn (Activity $activity) => $activity->getKudoCount(), $this->activities));
    }

    public function getMostKudotedActivity(): Activity
    {
        $mostKudotedActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getKudoCount() < $mostKudotedActivity->getKudoCount()) {
                continue;
            }
            $mostKudotedActivity = $activity;
        }

        return $mostKudotedActivity;
    }

    public function getFirstActivity(): Activity
    {
        $fistActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate() > $fistActivity->getStartDate()) {
                continue;
            }
            $fistActivity = $activity;
        }

        return $fistActivity;
    }

    public function getEarliestActivity(): Activity
    {
        $earliestActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() > $earliestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $earliestActivity = $activity;
        }

        return $earliestActivity;
    }

    public function getLatestActivity(): Activity
    {
        $latestActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getStartDate()->getMinutesSinceStartOfDay() < $latestActivity->getStartDate()->getMinutesSinceStartOfDay()) {
                continue;
            }
            $latestActivity = $activity;
        }

        return $latestActivity;
    }

    public function getLongestActivity(): Activity
    {
        $longestActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getDistance() < $longestActivity->getDistance()) {
                continue;
            }
            $longestActivity = $activity;
        }

        return $longestActivity;
    }

    public function getActivityWithHighestElevation(): Activity
    {
        $mostElevationActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getElevation() < $mostElevationActivity->getElevation()) {
                continue;
            }
            $mostElevationActivity = $activity;
        }

        return $mostElevationActivity;
    }

    public function getFastestActivity(): Activity
    {
        $fastestActivity = $this->activities[0];
        foreach ($this->activities as $activity) {
            if ($activity->getAverageSpeedInKmPerH() < $fastestActivity->getAverageSpeedInKmPerH()) {
                continue;
            }
            $fastestActivity = $activity;
        }

        return $fastestActivity;
    }

    public function getMostConsecutiveDaysOfCycling(): int
    {
        return DateCollection::fromDates(array_map(
            fn (Activity $activity) => $activity->getStartDate(),
            $this->activities,
        ))->countMostConsecutiveDates();
    }
}

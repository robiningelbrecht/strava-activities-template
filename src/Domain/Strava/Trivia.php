<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\DateCollection;

final readonly class Trivia
{
    private function __construct(
        private ActivityCollection $activities,
    ) {
    }

    public static function fromActivities(ActivityCollection $activities): self
    {
        return new self($activities);
    }

    public function getTotalKudosReceived(): int
    {
        return array_sum(array_map(fn (Activity $activity) => $activity->getKudoCount(), $this->activities->toArray()));
    }

    public function getMostKudotedActivity(): Activity
    {
        $mostKudotedActivity = $this->activities->toArray()[0];
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
        $fistActivity = $this->activities->toArray()[0];
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
        $earliestActivity = $this->activities->toArray()[0];
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
        $latestActivity = $this->activities->toArray()[0];
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
        $longestActivity = $this->activities->toArray()[0];
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
        $mostElevationActivity = $this->activities->toArray()[0];
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
        $fastestActivity = $this->activities->toArray()[0];
        foreach ($this->activities as $activity) {
            if ($activity->getAverageSpeedInKmPerH() < $fastestActivity->getAverageSpeedInKmPerH()) {
                continue;
            }
            $fastestActivity = $activity;
        }

        return $fastestActivity;
    }

    public function getMostConsecutiveDaysOfCycling(): DateCollection
    {
        return DateCollection::fromDates(array_map(
            fn (Activity $activity) => $activity->getStartDate(),
            $this->activities->toArray(),
        ))->getLongestConsecutiveDateRange();
    }
}

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
        return (int) $this->activities->sum(fn (Activity $activity) => $activity->getKudoCount());
    }

    public function getMostKudotedActivity(): Activity
    {
        $mostKudotedActivity = $this->activities->getFirst();
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
        $fistActivity = $this->activities->getFirst();
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
        $earliestActivity = $this->activities->getFirst();
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
        $latestActivity = $this->activities->getFirst();
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
        $longestActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getDistanceInKilometer() < $longestActivity->getDistanceInKilometer()) {
                continue;
            }
            $longestActivity = $activity;
        }

        return $longestActivity;
    }

    public function getActivityWithHighestElevation(): Activity
    {
        $mostElevationActivity = $this->activities->getFirst();
        foreach ($this->activities as $activity) {
            if ($activity->getElevationInMeter() < $mostElevationActivity->getElevationInMeter()) {
                continue;
            }
            $mostElevationActivity = $activity;
        }

        return $mostElevationActivity;
    }

    public function getFastestActivity(): Activity
    {
        $fastestActivity = $this->activities->getFirst();
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
        return DateCollection::fromDates($this->activities->map(
            fn (Activity $activity) => $activity->getStartDate(),
        ))->getLongestConsecutiveDateRange();
    }
}

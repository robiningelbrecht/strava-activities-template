<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use Carbon\CarbonInterval;

final readonly class DistanceBreakdown
{
    private function __construct(
        private ActivityCollection $activities,
    ) {
    }

    public static function fromActivities(ActivityCollection $activities): self
    {
        return new self($activities);
    }

    /**
     * @return array<mixed>
     */
    public function getRows(): array
    {
        $breakdownOnKm = 10;
        $statistics = [];
        $longestDistanceForActivity = max(array_map(
            fn (Activity $activity) => $activity->getDistance(),
            $this->activities->toArray()
        ));

        $range = range($breakdownOnKm, ceil($longestDistanceForActivity / $breakdownOnKm) * $breakdownOnKm, $breakdownOnKm);
        foreach ($range as $breakdownLimit) {
            $statistics[$breakdownLimit] = [
                'label' => sprintf('%d - %d km', $breakdownLimit - $breakdownOnKm, $breakdownLimit),
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
            ];
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $distanceBreakdown = ceil($activity->getDistance() / $breakdownOnKm) * $breakdownOnKm;

            ++$statistics[$distanceBreakdown]['numberOfRides'];
            $statistics[$distanceBreakdown]['totalDistance'] += $activity->getDistance();
            $statistics[$distanceBreakdown]['totalElevation'] += $activity->getElevation();
            $statistics[$distanceBreakdown]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$distanceBreakdown]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$distanceBreakdown]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        return $statistics;
    }
}

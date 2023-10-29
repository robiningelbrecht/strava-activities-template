<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearCollection;
use Carbon\CarbonInterval;

final readonly class BikeStatistics
{
    private function __construct(
        private ActivityCollection $activities,
        private GearCollection $bikes,
    ) {
    }

    public static function fromActivitiesAndGear(
        ActivityCollection $activities,
        GearCollection $bikes): self
    {
        return new self($activities, $bikes);
    }

    /**
     * @return array<mixed>
     */
    public function getRows(): array
    {
        $statistics = array_map(function (Gear $bike) {
            $activitiesWithBike = array_filter($this->activities->toArray(), fn (Activity $activity) => $activity->getGearId() == $bike->getId());

            return [
                'name' => sprintf('%s%s', $bike->getName(), $bike->isRetired() ? ' ☠️' : ''),
                'distance' => $bike->getDistanceInKm(),
                'numberOfRides' => count($activitiesWithBike),
                'movingTime' => CarbonInterval::seconds(array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $activitiesWithBike)))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'elevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activitiesWithBike)),
            ];
        }, $this->bikes->toArray());

        $activitiesWithOtherBike = array_filter($this->activities->toArray(), fn (Activity $activity) => empty($activity->getGearId()));
        if (0 === count($activitiesWithOtherBike)) {
            return $statistics;
        }

        $statistics[] = [
            'name' => 'Other',
            'distance' => array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $activitiesWithOtherBike)),
            'numberOfRides' => count($activitiesWithOtherBike),
            'movingTime' => CarbonInterval::seconds(array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $activitiesWithOtherBike)))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            'elevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activitiesWithOtherBike)),
        ];

        return $statistics;
    }
}

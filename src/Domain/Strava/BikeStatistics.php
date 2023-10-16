<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Gear\Gear;
use Carbon\CarbonInterval;

final readonly class BikeStatistics
{
    private function __construct(
        /** @var \App\Domain\Strava\Activity\Activity[] */
        private array $activities,
        /** @var \App\Domain\Strava\Gear\Gear[] */
        private array $bikes,
    ) {
    }

    public static function fromActivitiesAndGear(array $activities, array $gear): self
    {
        return new self($activities, $gear);
    }

    public function getRows(): array
    {
        $statistics = array_map(function (Gear $bike) {
            $activitiesWithBike = array_filter($this->activities, fn (Activity $activity) => $activity->getGearId() == $bike->getId());

            return [
                'name' => sprintf('%s%s', $bike->getName(), $bike->isRetired() ? ' ☠️' : ''),
                'distance' => $bike->getDistanceInKm(),
                'numberOfRides' => count($activitiesWithBike),
                'movingTime' => CarbonInterval::seconds(array_sum(array_map(fn (Activity $activity) => $activity->getMovingTime(), $activitiesWithBike)))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'elevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activitiesWithBike)),
            ];
        }, $this->bikes);

        $activitiesWithOtherBike = array_filter($this->activities, fn (Activity $activity) => empty($activity->getGearId()));
        $statistics[] = [
            'name' => 'Other',
            'distance' => array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $this->activities)) -
                array_sum(array_map(fn (Gear $bike) => $bike->getDistanceInKm(), $this->bikes)),
            'numberOfRides' => count($activitiesWithOtherBike),
            'movingTime' => CarbonInterval::seconds(array_sum(array_map(fn (Activity $activity) => $activity->getMovingTime(), $activitiesWithOtherBike)))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            'elevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activitiesWithOtherBike)),
            ];

        return $statistics;
    }
}

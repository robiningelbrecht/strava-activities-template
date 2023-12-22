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
        $numberOfBreakdowns = 11;
        $statistics = [];
        $longestDistanceForActivity = $this->activities->max(
            fn (Activity $activity) => $activity->getDistanceInKilometer()
        );

        $breakdownOnKm = ceil(($longestDistanceForActivity / $numberOfBreakdowns) / 5) * 5;

        $range = range($breakdownOnKm, ceil($longestDistanceForActivity / $breakdownOnKm) * $breakdownOnKm, $breakdownOnKm);
        foreach ($range as $breakdownLimit) {
            $statistics[$breakdownLimit] = [
                'label' => sprintf('%d - %d km', $breakdownLimit - $breakdownOnKm, $breakdownLimit),
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'averageDistance' => 0,
                'averageSpeed' => 0,
            ];
        }

        foreach ($this->activities as $activity) {
            /** @var Activity $activity */
            $distanceBreakdown = ceil($activity->getDistanceInKilometer() / $breakdownOnKm) * $breakdownOnKm;

            ++$statistics[$distanceBreakdown]['numberOfRides'];
            $statistics[$distanceBreakdown]['totalDistance'] += $activity->getDistanceInKilometer();
            $statistics[$distanceBreakdown]['totalElevation'] += $activity->getElevationInMeter();
            $statistics[$distanceBreakdown]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$distanceBreakdown]['averageDistance'] = $statistics[$distanceBreakdown]['totalDistance'] / $statistics[$distanceBreakdown]['numberOfRides'];
            if ($statistics[$distanceBreakdown]['movingTime'] > 0) {
                $statistics[$distanceBreakdown]['averageSpeed'] = ($statistics[$distanceBreakdown]['totalDistance'] / $statistics[$distanceBreakdown]['movingTime']) * 3600;
            }
            $statistics[$distanceBreakdown]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$distanceBreakdown]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        return $statistics;
    }
}

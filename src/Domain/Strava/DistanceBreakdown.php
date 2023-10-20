<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use Carbon\CarbonInterval;

final readonly class DistanceBreakdown
{
    /**
     * @param Activity[] $activities
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

    /**
     * @return array<mixed>
     */
    public function getRows(int $breakdownOnKm = 25): array
    {
        $statistics = [];
        $longestDistanceForActivity = max(array_map(
            fn (Activity $activity) => $activity->getDistance(),
            $this->activities
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

        foreach ($this->activities as $activity) {
            $distanceBreakdown = ceil($activity->getDistance() / $breakdownOnKm) * $breakdownOnKm;

            ++$statistics[$distanceBreakdown]['numberOfRides'];
            $statistics[$distanceBreakdown]['totalDistance'] += $activity->getDistance();
            $statistics[$distanceBreakdown]['totalElevation'] += $activity->getElevation();
            $statistics[$distanceBreakdown]['movingTime'] += $activity->getMovingTime();
            $statistics[$distanceBreakdown]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$distanceBreakdown]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        return $statistics;
    }
}

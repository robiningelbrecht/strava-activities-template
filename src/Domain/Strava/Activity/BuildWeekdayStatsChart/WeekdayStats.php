<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildWeekdayStatsChart;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use Carbon\CarbonInterval;

final readonly class WeekdayStats
{
    private function __construct(
        private ActivityCollection $activities,
    ) {
    }

    public static function fromActivities(
        ActivityCollection $activities,
    ): self {
        return new self($activities);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $statistics = [];
        $daysOfTheWeekMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $totalMovingTime = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $this->activities->toArray()));

        foreach ([1, 2, 3, 4, 5, 6, 0] as $weekDay) {
            $statistics[$daysOfTheWeekMap[$weekDay]] = [
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'percentage' => 0,
            ];
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $weekDay = $daysOfTheWeekMap[$activity->getStartDate()->format('w')];

            ++$statistics[$weekDay]['numberOfRides'];
            $statistics[$weekDay]['totalDistance'] += $activity->getDistance();
            $statistics[$weekDay]['totalElevation'] += $activity->getElevation();
            $statistics[$weekDay]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$weekDay]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$weekDay]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$weekDay]['percentage'] = round($statistics[$weekDay]['movingTime'] / $totalMovingTime * 100, 2);
        }

        return $statistics;
    }
}

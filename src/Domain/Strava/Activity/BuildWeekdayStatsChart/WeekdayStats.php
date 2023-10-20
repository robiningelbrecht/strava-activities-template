<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildWeekdayStatsChart;

use App\Domain\Strava\Activity\Activity;
use Carbon\CarbonInterval;

final readonly class WeekdayStats
{
    /**
     * @param Activity[] $activities
     */
    private function __construct(
        private array $activities,
    ) {
    }

    /**
     * @param Activity[] $activities
     */
    public static function fromActivities(
        array $activities,
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
        $totalMovingTime = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTime(), $this->activities));

        foreach ([1, 2, 3, 4, 5, 6, 0] as $weekDay) {
            $statistics[$daysOfTheWeekMap[$weekDay]] = [
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'percentage' => 0,
            ];
        }

        foreach ($this->activities as $activity) {
            $weekDay = $daysOfTheWeekMap[$activity->getStartDate()->format('w')];

            ++$statistics[$weekDay]['numberOfRides'];
            $statistics[$weekDay]['totalDistance'] += $activity->getDistance();
            $statistics[$weekDay]['totalElevation'] += $activity->getElevation();
            $statistics[$weekDay]['movingTime'] += $activity->getMovingTime();
            $statistics[$weekDay]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$weekDay]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$weekDay]['percentage'] = round($statistics[$weekDay]['movingTime'] / $totalMovingTime * 100, 2);
        }

        return $statistics;
    }
}

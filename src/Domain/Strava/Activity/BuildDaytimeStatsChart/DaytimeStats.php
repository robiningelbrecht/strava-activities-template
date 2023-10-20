<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildDaytimeStatsChart;

use App\Domain\Strava\Activity\Activity;
use Carbon\CarbonInterval;

final readonly class DaytimeStats
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
        $totalMovingTime = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTime(), $this->activities));

        foreach (Daytime::cases() as $daytime) {
            $statistics[$daytime->value] = [
                'daytime' => $daytime,
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'percentage' => 0,
            ];
        }

        foreach ($this->activities as $activity) {
            $daytime = Daytime::fromSerializableDateTime($activity->getStartDate());

            ++$statistics[$daytime->value]['numberOfRides'];
            $statistics[$daytime->value]['totalDistance'] += $activity->getDistance();
            $statistics[$daytime->value]['totalElevation'] += $activity->getElevation();
            $statistics[$daytime->value]['movingTime'] += $activity->getMovingTime();
            $statistics[$daytime->value]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$daytime->value]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$daytime->value]['percentage'] = round($statistics[$daytime->value]['movingTime'] / $totalMovingTime * 100, 2);
        }

        return $statistics;
    }
}

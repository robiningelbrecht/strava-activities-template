<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildDaytimeStatsChart;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use Carbon\CarbonInterval;

final readonly class DaytimeStats
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
        $totalMovingTime = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $this->activities->toArray()));

        foreach (Daytime::cases() as $daytime) {
            $statistics[$daytime->value] = [
                'daytime' => $daytime,
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'percentage' => 0,
                'averageDistance' => 0,
                'averageSpeed' => 0,
            ];
        }

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $daytime = Daytime::fromSerializableDateTime($activity->getStartDate());

            ++$statistics[$daytime->value]['numberOfRides'];
            $statistics[$daytime->value]['totalDistance'] += $activity->getDistance();
            $statistics[$daytime->value]['totalElevation'] += $activity->getElevation();
            $statistics[$daytime->value]['movingTime'] += $activity->getMovingTimeInSeconds();
            $statistics[$daytime->value]['averageDistance'] = $statistics[$daytime->value]['totalDistance'] / $statistics[$daytime->value]['numberOfRides'];
            if ($statistics[$daytime->value]['movingTime'] > 0) {
                $statistics[$daytime->value]['averageSpeed'] = ($statistics[$daytime->value]['totalDistance'] / $statistics[$daytime->value]['movingTime']) * 3600;
            }
            $statistics[$daytime->value]['movingTimeForHumans'] = CarbonInterval::seconds($statistics[$daytime->value]['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistics[$daytime->value]['percentage'] = round($statistics[$daytime->value]['movingTime'] / $totalMovingTime * 100, 2);
        }

        return $statistics;
    }
}

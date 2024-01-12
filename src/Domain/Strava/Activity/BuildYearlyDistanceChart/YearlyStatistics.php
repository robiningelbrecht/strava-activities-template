<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\BuildYearlyDistanceChart;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Infrastructure\ValueObject\Time\YearCollection;
use Carbon\CarbonInterval;

final readonly class YearlyStatistics
{
    private function __construct(
        private ActivityCollection $activities,
        private YearCollection $years,
    ) {
    }

    public static function fromActivities(
        ActivityCollection $activities,
        YearCollection $years
    ): self {
        return new self($activities, $years);
    }

    /**
     * @return array<mixed>
     */
    public function getStatistics(): array
    {
        $statistics = [];
        /** @var \App\Infrastructure\ValueObject\Time\Year $year */
        foreach ($this->years as $year) {
            $statistics[(string) $year] = [
                'year' => $year,
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'totalCalories' => 0,
                'movingTimeInSeconds' => 0,
            ];
        }

        $statistics = array_reverse($statistics, true);

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $year = $activity->getStartDate()->getYear();

            ++$statistics[$year]['numberOfRides'];
            $statistics[$year]['totalDistance'] += $activity->getDistanceInKilometer();
            $statistics[$year]['totalElevation'] += $activity->getElevationInMeter();
            $statistics[$year]['movingTimeInSeconds'] += $activity->getMovingTimeInSeconds();
            $statistics[$year]['totalCalories'] += $activity->getCalories();
        }

        $statistics = array_values($statistics);
        foreach ($statistics as $key => &$statistic) {
            $statistic['movingTime'] = CarbonInterval::seconds($statistic['movingTimeInSeconds'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
            $statistic['differenceInDistanceYearBefore'] = null;
            if (isset($statistics[$key + 1]['totalDistance'])) {
                $statistic['differenceInDistanceYearBefore'] = $statistic['totalDistance'] - $statistics[$key + 1]['totalDistance'];
            }
        }

        return $statistics;
    }
}

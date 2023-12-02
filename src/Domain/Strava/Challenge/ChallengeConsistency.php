<?php

declare(strict_types=1);

namespace App\Domain\Strava\Challenge;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Calendar\MonthCollection;
use App\Domain\Strava\MonthlyStatistics;

final readonly class ChallengeConsistency
{
    private MonthCollection $months;

    private function __construct(
        MonthCollection $months,
        private MonthlyStatistics $monthlyStatistics,
        private ActivityCollection $activities,
    ) {
        $this->months = $months->reverse();
    }

    public static function create(
        MonthCollection $months,
        MonthlyStatistics $monthlyStatistics,
        ActivityCollection $activities,
    ): self {
        return new self(
            months: $months,
            monthlyStatistics: $monthlyStatistics,
            activities: $activities
        );
    }

    public function getMonths(): MonthCollection
    {
        return $this->months;
    }

    /**
     * @return array<mixed>
     */
    public function getConsistencies(): array
    {
        $consistency = [];

        /** @var \App\Domain\Strava\Calendar\Month $month */
        foreach ($this->months as $month) {
            if (!$monthlyStats = $this->monthlyStatistics->getStatisticsForMonth($month)) {
                foreach (ConsistencyChallenge::cases() as $consistencyChallenge) {
                    $consistency[$consistencyChallenge->value][] = 0;
                }
                continue;
            }

            $activities = $this->activities->filterOnMonth($month);

            $consistency[ConsistencyChallenge::KM_200->value][] = $monthlyStats['totalDistance'] >= 200;
            $consistency[ConsistencyChallenge::KM_600->value][] = $monthlyStats['totalDistance'] >= 600;
            $consistency[ConsistencyChallenge::KM_1250->value][] = $monthlyStats['totalDistance'] >= 1250;
            $consistency[ConsistencyChallenge::CLIMBING_7500->value][] = $monthlyStats['totalElevation'] >= 7500;
            $consistency[ConsistencyChallenge::GRAN_FONDO->value][] = max(array_map(
                fn (Activity $activity) => $activity->getDistance(),
                $activities->toArray()
            )) >= 100;

            // First monday of the month until 4 weeks later, sunday.
            $startDate = $month->getFirstMonday();
            $hasTwoDaysOfActivity = true;
            for ($i = 0; $i < 4; ++$i) {
                $endDate = $startDate->add(new \DateInterval('P6D'));
                $numberOfActivities = count($this->activities->filterOnDateRange(
                    $startDate,
                    $endDate,
                ));
                if ($numberOfActivities < 2) {
                    $hasTwoDaysOfActivity = false;
                    break;
                }
                $startDate = $endDate->add(new \DateInterval('P1D'));
            }

            $consistency[ConsistencyChallenge::TWO_DAYS_OF_ACTIVITY_4_WEEKS->value][] = $hasTwoDaysOfActivity;
        }

        // Filter out challenges that have never been completed.
        foreach ($consistency as $challenge => $achievements) {
            if (!empty(array_filter($achievements))) {
                continue;
            }
            unset($consistency[$challenge]);
        }

        return $consistency;
    }
}

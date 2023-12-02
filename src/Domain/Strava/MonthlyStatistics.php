<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\MonthCollection;
use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeCollection;
use Carbon\CarbonInterval;

final readonly class MonthlyStatistics
{
    /** @var array<mixed> */
    private array $statistics;

    private function __construct(
        private ActivityCollection $activities,
        private ChallengeCollection $challenges,
        private MonthCollection $months,
    ) {
        $this->statistics = $this->buildStatistics();
    }

    public static function fromActivitiesAndChallenges(
        ActivityCollection $activities,
        ChallengeCollection $challenges,
        MonthCollection $months): self
    {
        return new self(
            activities: $activities,
            challenges: $challenges,
            months: $months
        );
    }

    /**
     * @return array<mixed>
     */
    private function buildStatistics(): array
    {
        $statistics = [];
        /** @var \App\Domain\Strava\Calendar\Month $month */
        foreach ($this->months as $month) {
            $statistics[$month->getId()] = [
                'id' => $month->getId(),
                'month' => $month->getLabel(),
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'totalCalories' => 0,
                'movingTimeInSeconds' => 0,
                'challengesCompleted' => count(array_filter(
                    $this->challenges->toArray(),
                    fn (Challenge $challenge) => $challenge->getCreatedOn()->format(Month::MONTH_ID_FORMAT) == $month->getId()
                )),
            ];
        }

        $statistics = array_reverse($statistics, true);

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $month = $activity->getStartDate()->format(Month::MONTH_ID_FORMAT);

            ++$statistics[$month]['numberOfRides'];
            $statistics[$month]['totalDistance'] += $activity->getDistance();
            $statistics[$month]['totalElevation'] += $activity->getElevation();
            $statistics[$month]['movingTimeInSeconds'] += $activity->getMovingTimeInSeconds();
            $statistics[$month]['totalCalories'] += $activity->getCalories();
        }

        $statistics = array_filter($statistics, fn (array $statistic) => $statistic['numberOfRides'] > 0);

        foreach ($statistics as &$statistic) {
            $statistic['movingTime'] = CarbonInterval::seconds($statistic['movingTimeInSeconds'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        return $statistics;
    }

    /**
     * @return array<mixed>
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * @return array<mixed>
     */
    public function getStatisticsForMonth(Month $month): array
    {
        return $this->statistics[$month->getId()] ?? [];
    }

    /**
     * @return array<mixed>
     */
    public function getTotals(): array
    {
        return $this->getTotalsForActivities($this->activities);
    }

    /**
     * @return array<mixed>
     */
    public function getTotalsForOutsideBikeRides(): array
    {
        $outsideBikeRides = $this->activities->filterOnActivityType(ActivityType::RIDE);

        return $this->getTotalsForActivities($outsideBikeRides);
    }

    /**
     * @return array<mixed>
     */
    public function getTotalsForZwift(): array
    {
        $virtualRides = $this->activities->filterOnActivityType(ActivityType::VIRTUAL_RIDE);

        return $this->getTotalsForActivities($virtualRides);
    }

    /**
     * @return array<mixed>
     */
    private function getTotalsForActivities(ActivityCollection $activities): array
    {
        $activities = $activities->toArray();

        return [
            'numberOfRides' => count($activities),
            'totalDistance' => array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $activities)),
            'totalElevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activities)),
            'totalCalories' => array_sum(array_map(fn (Activity $activity) => $activity->getCalories(), $activities)),
            'movingTime' => CarbonInterval::seconds(array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $activities)))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
        ];
    }
}

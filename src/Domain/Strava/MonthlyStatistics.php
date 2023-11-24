<?php

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Challenge\Challenge;
use App\Domain\Strava\Challenge\ChallengeCollection;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Carbon\CarbonInterval;

final readonly class MonthlyStatistics
{
    private function __construct(
        private ActivityCollection $activities,
        private ChallengeCollection $challenges,
        private SerializableDateTime $now,
    ) {
    }

    public static function fromActivitiesAndChallenges(
        ActivityCollection $activities,
        ChallengeCollection $challenges,
        SerializableDateTime $now): self
    {
        return new self($activities, $challenges, $now);
    }

    /**
     * @return array<mixed>
     */
    public function getRows(): array
    {
        $statistics = [];
        $startDate = $this->activities->getFirstActivityStartDate();

        $interval = new \DateInterval('P1M');
        $period = new \DatePeriod(
            $startDate->modify('first day of this month'),
            $interval,
            $this->now->modify('last day of this month')
        );

        foreach ($period as $date) {
            $month = $date->format('Ym');
            $statistics[$month] = [
                'month' => $date->format('F Y'),
                'numberOfRides' => 0,
                'totalDistance' => 0,
                'totalElevation' => 0,
                'movingTime' => 0,
                'challengesCompleted' => count(array_filter(
                    $this->challenges->toArray(),
                    fn (Challenge $challenge) => $challenge->getCreatedOn()->format('Ym') == $date->format('Ym')
                )),
            ];
        }

        $statistics = array_reverse($statistics, true);

        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $month = $activity->getStartDate()->format('Ym');

            ++$statistics[$month]['numberOfRides'];
            $statistics[$month]['totalDistance'] += $activity->getDistance();
            $statistics[$month]['totalElevation'] += $activity->getElevation();
            $statistics[$month]['movingTime'] += $activity->getMovingTimeInSeconds();
        }

        $statistics = array_filter($statistics, fn (array $statistic) => $statistic['numberOfRides'] > 0);

        foreach ($statistics as &$statistic) {
            $statistic['movingTime'] = CarbonInterval::seconds($statistic['movingTime'])->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']);
        }

        return $statistics;
    }

    /**
     * @return array<mixed>
     */
    public function getTotals(): array
    {
        return $this->getTotalsForActivities($this->activities->toArray());
    }

    /**
     * @return array<mixed>
     */
    public function getTotalsForOutsideBikeRides(): array
    {
        $outsideBikeRides = array_filter(
            $this->activities->toArray(),
            fn (Activity $activity) => ActivityType::RIDE === $activity->getType()
        );

        return $this->getTotalsForActivities($outsideBikeRides);
    }

    /**
     * @return array<mixed>
     */
    public function getTotalsForZwift(): array
    {
        $virtualRides = array_filter(
            $this->activities->toArray(),
            fn (Activity $activity) => ActivityType::VIRTUAL_RIDE === $activity->getType()
        );

        return $this->getTotalsForActivities($virtualRides);
    }

    /**
     * @param Activity[] $activities
     *
     * @return array<mixed>
     */
    private function getTotalsForActivities(array $activities): array
    {
        return [
            'numberOfRides' => count($activities),
            'totalDistance' => array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $activities)),
            'totalElevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activities)),
            'movingTime' => CarbonInterval::seconds(array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $activities)))->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
        ];
    }
}

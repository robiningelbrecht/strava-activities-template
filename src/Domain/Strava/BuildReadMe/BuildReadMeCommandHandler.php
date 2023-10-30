<?php

namespace App\Domain\Strava\BuildReadMe;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\DaytimeStats;
use App\Domain\Strava\Activity\BuildEddingtonChart\Eddington;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\WeekdayStats;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\BikeStatistics;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\DistanceBreakdown;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildReadMeCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ChallengeRepository $challengeRepository,
        private GearRepository $gearRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildReadMe);

        $allActivities = $this->activityRepository->findAll();

        foreach ($allActivities as &$activity) {
            if (!$activity->getGearId()) {
                continue;
            }
            $activity->enrichWithGearName(
                $this->gearRepository->find($activity->getGearId())->getName()
            );
        }
        $allChallenges = $this->challengeRepository->findAll();
        $allBikes = $this->gearRepository->findAll();

        $this->filesystem->write('README.md', $this->twig->load('readme.html.twig')->render([
            'totals' => ActivityTotals::fromActivities(
                $allActivities,
                SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
            ),
            'allActivities' => $this->twig->load('strava-activities.html.twig')->render([
                'activities' => $allActivities,
            ]),
            'monthlyStatistics' => MonthlyStatistics::fromActivitiesAndChallenges(
                activities: $allActivities,
                challenges: $allChallenges,
                now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
            ),
            'bikeStatistics' => BikeStatistics::fromActivitiesAndGear(
                activities: $allActivities,
                bikes: $allBikes
            ),
            'powerOutputs' => $this->activityPowerRepository->findBest(),
            'challenges' => $allChallenges,
            'eddington' => Eddington::fromActivities($allActivities),
            'weekdayStats' => WeekdayStats::fromActivities($allActivities),
            'daytimeStats' => DaytimeStats::fromActivities($allActivities),
            'distanceBreakdown' => DistanceBreakdown::fromActivities($allActivities),
        ]));
    }
}

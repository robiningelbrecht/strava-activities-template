<?php

namespace App\Domain\Strava\BuildReadMe;

use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\DaytimeStats;
use App\Domain\Strava\Activity\BuildEddingtonChart\Eddington;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\WeekdayStats;
use App\Domain\Strava\Activity\BuildYearlyDistanceChart\YearlyStatistics;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityPowerRepository;
use App\Domain\Strava\Calendar\MonthCollection;
use App\Domain\Strava\Challenge\ChallengeConsistency;
use App\Domain\Strava\Challenge\ReadModel\ChallengeDetailsRepository;
use App\Domain\Strava\DistanceBreakdown;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\Gear\ReadModel\GearDetailsRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Infrastructure\ValueObject\Time\YearCollection;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildReadMeCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityDetailsRepository $activityDetailsRepository,
        private ChallengeDetailsRepository $challengeDetailsRepository,
        private GearDetailsRepository $gearDetailsRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildReadMe);

        $now = SerializableDateTime::fromDateTimeImmutable($this->clock->now());
        $allActivities = $this->activityDetailsRepository->findAll();
        $allChallenges = $this->challengeDetailsRepository->findAll();
        $allBikes = $this->gearDetailsRepository->findAll();
        $allMonths = MonthCollection::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );
        $allYears = YearCollection::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            endDate: $now
        );
        $monthlyStatistics = MonthlyStatistics::fromActivitiesAndChallenges(
            activities: $allActivities,
            challenges: $allChallenges,
            months: $allMonths,
        );

        foreach ($allActivities as &$activity) {
            if (!$activity->getGearId()) {
                continue;
            }
            $activity->enrichWithGearName(
                $this->gearDetailsRepository->find($activity->getGearId())->getName()
            );
        }

        $this->filesystem->write('README.md', $this->twig->load('markdown/readme.html.twig')->render([
            'totals' => ActivityTotals::fromActivities(
                activities: $allActivities,
                now: $now,
            ),
            'allActivities' => $allActivities,
            'monthlyStatistics' => $monthlyStatistics,
            'bikeStatistics' => GearStatistics::fromActivitiesAndGear(
                activities: $allActivities,
                bikes: $allBikes
            ),
            'powerOutputs' => $this->activityPowerRepository->findBest(),
            'challengeConsistency' => ChallengeConsistency::create(
                months: $allMonths,
                monthlyStatistics: $monthlyStatistics,
                activities: $allActivities
            ),
            'challenges' => $allChallenges,
            'eddington' => Eddington::fromActivities($allActivities),
            'weekdayStats' => WeekdayStats::fromActivities($allActivities),
            'daytimeStats' => DaytimeStats::fromActivities($allActivities),
            'distanceBreakdown' => DistanceBreakdown::fromActivities($allActivities),
            'yearlyStatistics' => YearlyStatistics::fromActivities(
                activities: $allActivities,
                years: $allYears
            ),
        ]));
    }
}

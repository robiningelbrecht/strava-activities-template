<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Domain\Strava\Activity\ActivityHighlights;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\ActivityType;
use App\Domain\Strava\Activity\BuildActivityHeatmapChart\ActivityHeatmapChartBuilder;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\DaytimeStats;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\DaytimeStatsChartsBuilder;
use App\Domain\Strava\Activity\BuildEddingtonChart\Eddington;
use App\Domain\Strava\Activity\BuildEddingtonChart\EddingtonChartBuilder;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\WeekdayStats;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\WeekdayStatsChartsBuilder;
use App\Domain\Strava\Activity\BuildWeeklyDistanceChart\WeeklyDistanceChartBuilder;
use App\Domain\Strava\Activity\HeartRateDistributionChartBuilder;
use App\Domain\Strava\Activity\Image\Image;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;
use App\Domain\Strava\Athlete\AthleteWeightRepository;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Domain\Strava\Athlete\TimeInHeartRateZoneChartBuilder;
use App\Domain\Strava\BikeStatistics;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\DistanceBreakdown;
use App\Domain\Strava\Ftp\FtpHistoryChartBuilder;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Domain\Strava\Trivia;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildHtmlVersionCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ChallengeRepository $challengeRepository,
        private GearRepository $gearRepository,
        private ImageRepository $imageRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private AthleteWeightRepository $athleteWeightRepository,
        private FtpRepository $ftpRepository,
        private KeyValueStore $keyValueStore,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildHtmlVersion);

        $now = SerializableDateTime::fromDateTimeImmutable($this->clock->now());
        $athleteBirthday = SerializableDateTime::fromString($this->keyValueStore->find(Key::ATHLETE_BIRTHDAY)->getValue());

        $allActivities = $this->activityRepository->findAll();
        $allChallenges = $this->challengeRepository->findAll();
        $allBikes = $this->gearRepository->findAll();
        $allImages = $this->imageRepository->findAll();
        $allFtps = $this->ftpRepository->findAll();
        $eddington = Eddington::fromActivities($allActivities);
        $activityHighlights = ActivityHighlights::fromActivities($allActivities);
        $weekdayStats = WeekdayStats::fromActivities($allActivities);
        $dayTimeStats = DaytimeStats::fromActivities($allActivities);

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($allActivities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->activityPowerRepository->findBestForActivity($activity->getId())
            );

            try {
                $ftp = $this->ftpRepository->find($activity->getStartDate());
                $activity->enrichWithFtp($ftp->getFtp());
            } catch (EntityNotFound) {
            }
            $activity->enrichWithAthleteBirthday($athleteBirthday);

            if (!$activity->getGearId()) {
                continue;
            }
            $activity->enrichWithGearName(
                $this->gearRepository->find($activity->getGearId())->getName()
            );
        }

        /** @var \App\Domain\Strava\Ftp\Ftp $ftp */
        foreach ($allFtps as $ftp) {
            try {
                $ftp->enrichWithAthleteWeight(
                    $this->athleteWeightRepository->find($ftp->getSetOn())
                );
            } catch (EntityNotFound) {
            }
        }

        $this->filesystem->write(
            'build/html/index.html',
            $this->twig->load('html/index.html.twig')->render([
                'totalActivityCount' => count($allActivities),
                'eddingtonNumber' => $eddington->getNumber(),
                'completedChallenges' => count($allChallenges),
                'totalPhotoCount' => count($allImages),
            ]),
        );

        $this->filesystem->write(
            'build/html/dashboard.html',
            $this->twig->load('html/dashboard.html.twig')->render([
                'mostRecentActivities' => array_slice($allActivities->toArray(), 0, 5),
                'activityHighlights' => $activityHighlights,
                'intro' => ActivityTotals::fromActivities(
                    $allActivities,
                    $now,
                ),
                'weeklyDistanceChart' => Json::encode(
                    WeeklyDistanceChartBuilder::fromActivities(
                        $allActivities,
                        $now,
                    )
                        ->withAnimation(true)
                        ->withoutBackgroundColor()
                        ->build(),
                ),
                'bikeStatistics' => BikeStatistics::fromActivitiesAndGear(
                    activities: $allActivities,
                    bikes: $allBikes
                ),
                'powerOutputs' => $this->activityPowerRepository->findBest(),
                'activityHeatmapChart' => Json::encode(
                    ActivityHeatmapChartBuilder::fromActivities(
                        activities: $allActivities,
                        now: $now,
                    )
                        ->withAnimation(true)
                        ->build()
                ),
                'weekdayStatsChart' => Json::encode(
                    WeekdayStatsChartsBuilder::fromWeekdayStats($weekdayStats)
                        ->withoutBackgroundColor()
                        ->withAnimation(true)
                        ->build(),
                ),
                'weekdayStats' => $weekdayStats,
                'daytimeStatsChart' => Json::encode(
                    DaytimeStatsChartsBuilder::fromDaytimeStats($dayTimeStats)
                        ->withoutBackgroundColor()
                        ->withAnimation(true)
                        ->build(),
                ),
                'daytimeStats' => $dayTimeStats,
                'distanceBreakdown' => DistanceBreakdown::fromActivities($allActivities),
                'trivia' => Trivia::fromActivities($allActivities),
                'ftpHistoryChart' => !$allFtps->isEmpty() ? Json::encode(
                    FtpHistoryChartBuilder::fromFtps(
                        ftps: $allFtps,
                        now: SerializableDateTime::fromDateTimeImmutable($this->clock->now())
                    )
                    ->withoutBackgroundColor()
                    ->withAnimation(true)
                    ->build()
                ) : null,
                'timeInHeartRateZoneChart' => Json::encode(
                    TimeInHeartRateZoneChartBuilder::fromTimeInZones(
                        timeInSecondsInHeartRateZoneOne: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::ONE),
                        timeInSecondsInHeartRateZoneTwo: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::TWO),
                        timeInSecondsInHeartRateZoneThree: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::THREE),
                        timeInSecondsInHeartRateZoneFour: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FOUR),
                        timeInSecondsInHeartRateZoneFive: $this->activityHeartRateRepository->findTotalTimeInSecondsInHeartRateZone(HeartRateZone::FIVE),
                    )
                    ->build(),
                ),
            ]),
        );

        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activities.html.twig')->render([
                'timeIntervals' => ActivityPowerRepository::TIME_INTERVAL_IN_SECONDS,
                'activities' => $allActivities,
                'activityHighlights' => $activityHighlights,
            ]),
        );

        $this->filesystem->write(
            'build/html/photos.html',
            $this->twig->load('html/photos.html.twig')->render([
                'rideImagesCount' => count(array_filter($allImages, fn (Image $image) => ActivityType::RIDE === $image->getActivity()->getType())),
                'virtualRideImagesCount' => count(array_filter($allImages, fn (Image $image) => ActivityType::VIRTUAL_RIDE === $image->getActivity()->getType())),
                'images' => $allImages,
            ]),
        );

        $challengesGroupedByMonth = [];
        foreach ($allChallenges as $challenge) {
            $challengesGroupedByMonth[$challenge->getCreatedOn()->format('F Y')][] = $challenge;
        }
        $this->filesystem->write(
            'build/html/challenges.html',
            $this->twig->load('html/challenges.html.twig')->render([
                'challengesGroupedPerMonth' => $challengesGroupedByMonth,
            ]),
        );

        $this->filesystem->write(
            'build/html/eddington.html',
            $this->twig->load('html/eddington.html.twig')->render([
                'eddingtonChart' => Json::encode(
                    EddingtonChartBuilder::fromEddington(
                        Eddington::fromActivities($allActivities)
                    )
                        ->withAnimation(true)
                        ->withoutBackgroundColor()
                        ->build(),
                ),
                'eddington' => $eddington,
            ]),
        );

        $this->filesystem->write(
            'build/html/monthly-stats.html',
            $this->twig->load('html/monthly-stats.html.twig')->render([
                'monthlyStatistics' => MonthlyStatistics::fromActivitiesAndChallenges(
                    activities: $allActivities,
                    challenges: $allChallenges,
                    now: SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                ),
            ]),
        );

        foreach ($allActivities as $activity) {
            $streams = $this->activityStreamRepository->findByActivityAndStreamTypes(
                activityId: $activity->getId(),
                streamTypes: StreamTypeCollection::fromArray([
                    StreamType::VELOCITY,
                    StreamType::WATTS,
                    StreamType::HEART_RATE,
                    StreamType::CADENCE,
                ])
            );

            if ($cadenceStream = $streams->getByStreamType(StreamType::CADENCE)) {
                $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
            }

            $this->filesystem->write(
                'build/html/activity/activity-'.$activity->getId().'.html',
                $this->twig->load('html/activity.html.twig')->render([
                    'activity' => $activity,
                    'heartRateDistributionChart' => Json::encode(
                        HeartRateDistributionChartBuilder::fromHeartRateData()->build(),
                    ),
                ]),
            );
        }
    }
}

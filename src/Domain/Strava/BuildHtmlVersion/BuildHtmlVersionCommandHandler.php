<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Domain\Strava\Activity\ActivityHighlights;
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
use App\Domain\Strava\Activity\PowerDistributionChartBuilder;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypeCollection;
use App\Domain\Strava\Athlete\HeartRateZone;
use App\Domain\Strava\Athlete\ReadModel\AthleteWeightRepository;
use App\Domain\Strava\Athlete\TimeInHeartRateZoneChartBuilder;
use App\Domain\Strava\Calendar\Calendar;
use App\Domain\Strava\Calendar\Month;
use App\Domain\Strava\Calendar\MonthCollection;
use App\Domain\Strava\Challenge\ChallengeConsistency;
use App\Domain\Strava\Challenge\ReadModel\ChallengeDetailsRepository;
use App\Domain\Strava\DistanceBreakdown;
use App\Domain\Strava\Ftp\FtpHistoryChartBuilder;
use App\Domain\Strava\Ftp\ReadModel\FtpDetailsRepository;
use App\Domain\Strava\Gear\DistanceOverTimePerGearChartBuilder;
use App\Domain\Strava\Gear\GearStatistics;
use App\Domain\Strava\Gear\ReadModel\GearDetailsRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Domain\Strava\Segment\ReadModel\SegmentDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\ReadModel\SegmentEffortDetailsRepository;
use App\Domain\Strava\Trivia;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\ReadModel\KeyValueStore;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildHtmlVersionCommandHandler implements CommandHandler
{
    public function __construct(
        private ActivityDetailsRepository $activityDetailsRepository,
        private ChallengeDetailsRepository $challengeDetailsRepository,
        private GearDetailsRepository $gearDetailsRepository,
        private ImageRepository $imageRepository,
        private ActivityPowerRepository $activityPowerRepository,
        private ActivityStreamDetailsRepository $activityStreamDetailsRepository,
        private ActivityHeartRateRepository $activityHeartRateRepository,
        private AthleteWeightRepository $athleteWeightRepository,
        private SegmentDetailsRepository $segmentDetailsRepository,
        private SegmentEffortDetailsRepository $segmentEffortDetailsRepository,
        private FtpDetailsRepository $ftpDetailsRepository,
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

        $allActivities = $this->activityDetailsRepository->findAll();
        $allChallenges = $this->challengeDetailsRepository->findAll();
        $allBikes = $this->gearDetailsRepository->findAll();
        $allImages = $this->imageRepository->findAll();
        $allFtps = $this->ftpDetailsRepository->findAll();
        $allSegments = $this->segmentDetailsRepository->findAll();
        $eddington = Eddington::fromActivities($allActivities);
        $activityHighlights = ActivityHighlights::fromActivities($allActivities);
        $weekdayStats = WeekdayStats::fromActivities($allActivities);
        $dayTimeStats = DaytimeStats::fromActivities($allActivities);
        $allMonths = MonthCollection::create(
            startDate: $allActivities->getFirstActivityStartDate(),
            now: $now
        );
        $monthlyStatistics = MonthlyStatistics::fromActivitiesAndChallenges(
            activities: $allActivities,
            challenges: $allChallenges,
            months: $allMonths,
        );

        /** @var \App\Domain\Strava\Activity\Activity $activity */
        foreach ($allActivities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->activityPowerRepository->findBestForActivity($activity->getId())
            );

            try {
                $ftp = $this->ftpDetailsRepository->find($activity->getStartDate());
                $activity->enrichWithFtp($ftp->getFtp());
            } catch (EntityNotFound) {
            }
            $activity->enrichWithAthleteBirthday($athleteBirthday);

            if (!$activity->getGearId()) {
                continue;
            }
            $activity->enrichWithGearName(
                $this->gearDetailsRepository->find($activity->getGearId())->getName()
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
                    activities: $allActivities,
                    now: $now,
                ),
                'weeklyDistanceChart' => Json::encode(
                    WeeklyDistanceChartBuilder::fromActivities(
                        $allActivities,
                        $now,
                    )
                        ->withAnimation(true)
                        ->withoutBackgroundColor()
                        ->withDataZoom(true)
                        ->build(),
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
                        now: $now
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
                'heartRates' => $this->activityHeartRateRepository->findHighest(),
                'challengeConsistency' => ChallengeConsistency::create(
                    months: $allMonths,
                    monthlyStatistics: $monthlyStatistics,
                    activities: $allActivities
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
                    EddingtonChartBuilder::fromEddington($eddington)
                        ->withAnimation(true)
                        ->withoutBackgroundColor()
                        ->build(),
                ),
                'eddington' => $eddington,
            ]),
        );

        /** @var \App\Domain\Strava\Segment\Segment $segment */
        foreach ($allSegments as $segment) {
            $segmentEfforts = $this->segmentEffortDetailsRepository->findBySegmentId($segment->getId());
            if ($bestSegmentEffort = $segmentEfforts->getBestEffort()) {
                $segment->enrichWithBestEffort($bestSegmentEffort);
            }

            /** @var \App\Domain\Strava\Segment\SegmentEffort\SegmentEffort $segmentEffort */
            foreach ($segmentEfforts as $segmentEffort) {
                $segmentEffort->enrichWithActivity($allActivities->getByActivityId($segmentEffort->getActivityId()));
            }

            $this->filesystem->write(
                'build/html/segment/'.$segment->getId().'.html',
                $this->twig->load('html/segment.html.twig')->render([
                    'segment' => $segment,
                    'segmentEfforts' => $segmentEfforts,
                ]),
            );
        }

        $this->filesystem->write(
            'build/html/segments.html',
            $this->twig->load('html/segments.html.twig')->render([
                'segments' => $allSegments,
            ]),
        );

        $this->filesystem->write(
            'build/html/monthly-stats.html',
            $this->twig->load('html/monthly-stats.html.twig')->render([
                'monthlyStatistics' => $monthlyStatistics,
            ]),
        );

        /** @var Month $month */
        foreach ($allMonths as $month) {
            $this->filesystem->write(
                'build/html/month/month-'.$month->getId().'.html',
                $this->twig->load('html/month.html.twig')->render([
                    'hasPreviousMonth' => $month->getId() != $allActivities->getFirstActivityStartDate()->format(Month::MONTH_ID_FORMAT),
                    'hasNextMonth' => $month->getId() != $now->format(Month::MONTH_ID_FORMAT),
                    'statistics' => $monthlyStatistics->getStatisticsForMonth($month),
                    'calendar' => Calendar::create(
                        month: $month,
                        activities: $allActivities
                    ),
                ]),
            );
        }

        $this->filesystem->write(
            'build/html/gear-stats.html',
            $this->twig->load('html/gear-stats.html.twig')->render([
                'bikeStatistics' => GearStatistics::fromActivitiesAndGear(
                    activities: $allActivities,
                    bikes: $allBikes
                ),
                'distanceOverTimePerGearChart' => Json::encode(
                    DistanceOverTimePerGearChartBuilder::fromGearAndActivities(
                        gearCollection: $allBikes,
                        activityCollection: $allActivities,
                        months: $allMonths,
                    )
                        ->build()
                ),
            ]),
        );

        foreach ($allActivities as $activity) {
            $streams = $this->activityStreamDetailsRepository->findByActivityAndStreamTypes(
                activityId: $activity->getId(),
                streamTypes: StreamTypeCollection::fromArray([StreamType::CADENCE])
            );

            if ($cadenceStream = $streams->getByStreamType(StreamType::CADENCE)) {
                $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
            }

            $heartRateData = $this->activityHeartRateRepository->findTimeInSecondsPerHeartRateForActivity($activity->getId());
            $powerData = $this->activityPowerRepository->findTimeInSecondsPerWattageForActivity($activity->getId());
            $leafletMap = $activity->getLeafletMap();

            $this->filesystem->write(
                'build/html/activity/'.$activity->getId().'.html',
                $this->twig->load('html/activity.html.twig')->render([
                    'activity' => $activity,
                    'leaflet' => $leafletMap ? [
                        'routes' => [$activity->getPolylineSummary()],
                        'map' => $leafletMap,
                    ] : null,
                    'heartRateDistributionChart' => $heartRateData ? Json::encode(
                        HeartRateDistributionChartBuilder::fromHeartRateData(
                            heartRateData: $heartRateData,
                            averageHeartRate: $activity->getAverageHeartRate(),
                            athleteMaxHeartRate: $activity->getAthleteMaxHeartRate()
                        )->build(),
                    ) : null,
                    'powerDistributionChart' => $powerData ? Json::encode(
                        PowerDistributionChartBuilder::fromPowerData(
                            powerData: $powerData,
                            averagePower: $activity->getAveragePower(),
                        )->build(),
                    ) : null,
                ]),
            );
        }
    }
}

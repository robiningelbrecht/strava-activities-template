<?php

namespace App\Domain\Strava\BuildHtmlVersion;

use App\Domain\Strava\Activity\ActivityHighlights;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\BuildActivityHeatmapChart\ActivityHeatmapChartBuilder;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\DaytimeStats;
use App\Domain\Strava\Activity\BuildDaytimeStatsChart\DaytimeStatsChartsBuilder;
use App\Domain\Strava\Activity\BuildEddingtonChart\Eddington;
use App\Domain\Strava\Activity\BuildEddingtonChart\EddingtonChartBuilder;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\WeekdayStats;
use App\Domain\Strava\Activity\BuildWeekdayStatsChart\WeekdayStatsChartsBuilder;
use App\Domain\Strava\Activity\BuildWeeklyDistanceChart\WeeklyDistanceChartBuilder;
use App\Domain\Strava\Activity\Image\ActivityBasedImageRepository;
use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Domain\Strava\Activity\Stream\ActivityStream;
use App\Domain\Strava\Activity\Stream\StravaActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\StravaActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamChartBuilder;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\BikeStatistics;
use App\Domain\Strava\Challenge\StravaChallengeRepository;
use App\Domain\Strava\Gear\StravaGearRepository;
use App\Domain\Strava\MonthlyStatistics;
use App\Domain\Strava\Trivia;
use App\Infrastructure\Attribute\AsCommandHandler;
use App\Infrastructure\CQRS\CommandHandler\CommandHandler;
use App\Infrastructure\CQRS\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Lcobucci\Clock\Clock;
use League\Flysystem\FilesystemOperator;
use Twig\Environment;

#[AsCommandHandler]
final readonly class BuildHtmlVersionCommandHandler implements CommandHandler
{
    public function __construct(
        private StravaActivityRepository $stravaActivityRepository,
        private StravaChallengeRepository $stravaChallengeRepository,
        private StravaGearRepository $stravaGearRepository,
        private ActivityBasedImageRepository $activityBasedImageRepository,
        private StravaActivityPowerRepository $stravaActivityPowerRepository,
        private StravaActivityStreamRepository $stravaActivityStreamRepository,
        private Environment $twig,
        private FilesystemOperator $filesystem,
        private Clock $clock,
    ) {
    }

    public function handle(DomainCommand $command): void
    {
        assert($command instanceof BuildHtmlVersion);

        $now = SerializableDateTime::fromDateTimeImmutable($this->clock->now());

        $allActivities = $this->stravaActivityRepository->findAll();
        $allChallenges = $this->stravaChallengeRepository->findAll();
        $allBikes = $this->stravaGearRepository->findAll();
        $allImages = $this->activityBasedImageRepository->findAll();
        $eddington = Eddington::fromActivities($allActivities);
        $activityHighlights = ActivityHighlights::fromActivities($allActivities);
        $weekdayStats = WeekdayStats::fromActivities($allActivities);
        $dayTimeStats = DaytimeStats::fromActivities($allActivities);

        foreach ($allActivities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->stravaActivityPowerRepository->findBestForActivity($activity->getId())
            );

            if (!$activity->getGearId()) {
                continue;
            }
            $activity->enrichWithGearName(
                $this->stravaGearRepository->find($activity->getGearId())->getName()
            );
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
                'mostRecentActivities' => array_slice($allActivities, 0, 5),
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
                    gear: $allBikes
                ),
                'powerOutputs' => $this->stravaActivityPowerRepository->findBest(),
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
                'trivia' => Trivia::fromActivities($allActivities),
            ]),
        );

        $this->filesystem->write(
            'build/html/activities.html',
            $this->twig->load('html/activities.html.twig')->render([
                'timeIntervals' => StravaActivityPowerRepository::TIME_INTERVAL_IN_SECONDS,
                'activities' => $allActivities,
                'activityHighlights' => $activityHighlights,
            ]),
        );

        $this->filesystem->write(
            'build/html/photos.html',
            $this->twig->load('html/photos.html.twig')->render([
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
                    $allActivities,
                    $allChallenges,
                    SerializableDateTime::fromDateTimeImmutable($this->clock->now()),
                ),
            ]),
        );

        foreach ($allActivities as $activity) {
            $streams = $this->stravaActivityStreamRepository->findByActivityAndStreamTypes(
                $activity->getId(),
                [
                    StreamType::VELOCITY,
                    StreamType::WATTS,
                    StreamType::HEART_RATE,
                    StreamType::CADENCE,
                ]
            );

            if ($cadenceStreams = array_filter(
                $streams,
                fn (ActivityStream $stream) => StreamType::CADENCE === $stream->getStreamType()
            )) {
                $activity->enrichWithMaxCadence(max(reset($cadenceStreams)->getData()));
            }

            $this->filesystem->write(
                'build/html/activity/activity-'.$activity->getId().'.html',
                $this->twig->load('html/activity.html.twig')->render([
                    'timeIntervals' => StravaActivityPowerRepository::TIME_INTERVAL_IN_SECONDS,
                    'activity' => $activity,
                    'charts' => array_map(
                        fn (ActivityStream $stream) => Json::encode(StreamChartBuilder::fromStream($stream)->build()),
                        $streams
                    ),
                ]),
            );
        }
    }
}

<?php

use App\Domain\Strava\Activity\Image\ActivityBasedImageRepository;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\ReadModel\ActivityDetailsRepository;
use App\Domain\Strava\Activity\ReadModel\DbalActivityDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\ActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\DbalActivityStreamDetailsRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\StreamBasedActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ReadModel\StreamBasedActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\WriteModel\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\WriteModel\DbalActivityStreamRepository;
use App\Domain\Strava\Activity\WriteModel\ActivityRepository;
use App\Domain\Strava\Activity\WriteModel\DbalActivityRepository;
use App\Domain\Strava\Athlete\ReadModel\ActivityBasedAthleteWeightRepository;
use App\Domain\Strava\Athlete\ReadModel\AthleteWeightRepository;
use App\Domain\Strava\Challenge\ReadModel\ChallengeDetailsRepository;
use App\Domain\Strava\Challenge\ReadModel\DbalChallengeDetailsRepository;
use App\Domain\Strava\Challenge\WriteModel\ChallengeRepository;
use App\Domain\Strava\Challenge\WriteModel\DbalChallengeRepository;
use App\Domain\Strava\Ftp\ReadModel\DbalFtpDetailsRepository;
use App\Domain\Strava\Ftp\ReadModel\FtpDetailsRepository;
use App\Domain\Strava\Ftp\WriteModel\DbalFtpRepository;
use App\Domain\Strava\Ftp\WriteModel\FtpRepository;
use App\Domain\Strava\Gear\ReadModel\DbalGearDetailsRepository;
use App\Domain\Strava\Gear\ReadModel\GearDetailsRepository;
use App\Domain\Strava\Gear\WriteModel\DbalGearRepository;
use App\Domain\Strava\Gear\WriteModel\GearRepository;
use App\Domain\Strava\Segment\ReadModel\DbalSegmentDetailsRepository;
use App\Domain\Strava\Segment\ReadModel\SegmentDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\ReadModel\DbalSegmentEffortDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\ReadModel\SegmentEffortDetailsRepository;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\DbalSegmentEffortRepository;
use App\Domain\Strava\Segment\SegmentEffort\WriteModel\SegmentEffortRepository;
use App\Domain\Strava\Segment\WriteModel\DbalSegmentRepository;
use App\Domain\Strava\Segment\WriteModel\SegmentRepository;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\StravaRefreshToken;
use App\Domain\Weather\OpenMeteo\LiveOpenMeteo;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\FileSystem\FileRepository;
use App\Infrastructure\FileSystem\SystemFileRepository;
use App\Infrastructure\KeyValue\ReadModel\DbalKeyValueStore as DbalKeyValueReadStore;
use App\Infrastructure\KeyValue\ReadModel\KeyValueStore as KeyValueReadStore;
use App\Infrastructure\KeyValue\WriteModel\DbalKeyValueStore as DbalKeyValueWriteStore;
use App\Infrastructure\KeyValue\WriteModel\KeyValueStore as KeyValueWriteStore;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\Time\SystemSleep;
use App\Infrastructure\Twig\TwigBuilder;
use App\Infrastructure\ValueObject\RandomUuidFactory;
use App\Infrastructure\ValueObject\UuidFactory;
use Dotenv\Dotenv;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Application;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

$appRoot = Settings::getAppRoot();

$dotenv = Dotenv::createImmutable($appRoot);
$dotenv->load();

return [
    Clock::class => DI\factory([SystemClock::class, 'fromSystemTimezone']),
    Sleep::class => DI\create(SystemSleep::class),
    UuidFactory::class => DI\create(RandomUuidFactory::class),
    KeyValueWriteStore::class => DI\get(DbalKeyValueWriteStore::class),
    KeyValueReadStore::class => DI\get(DbalKeyValueReadStore::class),
    OpenMeteo::class => DI\get(LiveOpenMeteo::class),
    // Repositories
    AthleteWeightRepository::class => DI\autowire(ActivityBasedAthleteWeightRepository::class),
    ActivityRepository::class => DI\autowire(DbalActivityRepository::class),
    ActivityDetailsRepository::class => DI\autowire(DbalActivityDetailsRepository::class),
    ActivityStreamRepository::class => DI\autowire(DbalActivityStreamRepository::class),
    ActivityStreamDetailsRepository::class => DI\autowire(DbalActivityStreamDetailsRepository::class),
    ActivityPowerRepository::class => DI\autowire(StreamBasedActivityPowerRepository::class),
    ActivityHeartRateRepository::class => DI\autowire(StreamBasedActivityHeartRateRepository::class),
    ImageRepository::class => DI\autowire(ActivityBasedImageRepository::class),
    ChallengeRepository::class => DI\autowire(DbalChallengeRepository::class),
    ChallengeDetailsRepository::class => DI\autowire(DbalChallengeDetailsRepository::class),
    FtpRepository::class => DI\autowire(DbalFtpRepository::class),
    FtpDetailsRepository::class => DI\autowire(DbalFtpDetailsRepository::class),
    GearRepository::class => DI\autowire(DbalGearRepository::class),
    GearDetailsRepository::class => DI\autowire(DbalGearDetailsRepository::class),
    SegmentRepository::class => DI\autowire(DbalSegmentRepository::class),
    SegmentDetailsRepository::class => DI\autowire(DbalSegmentDetailsRepository::class),
    SegmentEffortRepository::class => DI\autowire(DbalSegmentEffortRepository::class),
    SegmentEffortDetailsRepository::class => DI\autowire(DbalSegmentEffortDetailsRepository::class),
    // Twig Environment.
    FilesystemLoader::class => DI\create(FilesystemLoader::class)->constructor($appRoot.'/templates'),
    TwigEnvironment::class => DI\factory([TwigBuilder::class, 'build']),
    // Console command application.
    Application::class => function (ConsoleCommandContainer $consoleCommandContainer) {
        $application = new Application();
        foreach ($consoleCommandContainer->getCommands() as $command) {
            $application->add($command);
        }

        return $application;
    },
    // Environment.
    Environment::class => fn () => Environment::from($_ENV['ENVIRONMENT']),
    // Settings.
    Settings::class => DI\factory([Settings::class, 'load']),
    // Strava stuff.
    StravaClientId::class => StravaClientId::fromString($_ENV['STRAVA_CLIENT_ID']),
    StravaClientSecret::class => StravaClientSecret::fromString($_ENV['STRAVA_CLIENT_SECRET']),
    StravaRefreshToken::class => StravaRefreshToken::fromString($_ENV['STRAVA_REFRESH_TOKEN']),
    // File system.
    FilesystemOperator::class => DI\create(Filesystem::class)->constructor(new LocalFilesystemAdapter(
        Settings::getAppRoot()
    )),
    FileRepository::class => DI\autowire(SystemFileRepository::class),
];

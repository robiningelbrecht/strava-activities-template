<?php

use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\DbalActivityRepository;
use App\Domain\Strava\Activity\Image\ActivityBasedImageRepository;
use App\Domain\Strava\Activity\Image\ImageRepository;
use App\Domain\Strava\Activity\Stream\ActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\DbalActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamBasedActivityHeartRateRepository;
use App\Domain\Strava\Activity\Stream\StreamBasedActivityPowerRepository;
use App\Domain\Strava\Athlete\ActivityBasedAthleteWeightRepository;
use App\Domain\Strava\Athlete\AthleteWeightRepository;
use App\Domain\Strava\Challenge\ChallengeRepository;
use App\Domain\Strava\Challenge\DbalChallengeRepository;
use App\Domain\Strava\Ftp\DbalFtpRepository;
use App\Domain\Strava\Ftp\FtpRepository;
use App\Domain\Strava\Gear\DbalGearRepository;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\StravaRefreshToken;
use App\Domain\Weather\OpenMeteo\LiveOpenMeteo;
use App\Domain\Weather\OpenMeteo\OpenMeteo;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\KeyValue\DbalKeyValueStore;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Time\Sleep;
use App\Infrastructure\Time\SystemSleep;
use App\Infrastructure\Twig\TwigBuilder;
use App\Infrastructure\ValueObject\RandomUuidFactory;
use App\Infrastructure\ValueObject\UuidFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
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
    KeyValueStore::class => DI\get(DbalKeyValueStore::class),
    OpenMeteo::class => DI\get(LiveOpenMeteo::class),
    // Repositories
    AthleteWeightRepository::class => DI\autowire(ActivityBasedAthleteWeightRepository::class),
    ActivityRepository::class => DI\autowire(DbalActivityRepository::class),
    ActivityStreamRepository::class => DI\autowire(DbalActivityStreamRepository::class),
    ActivityPowerRepository::class => DI\autowire(StreamBasedActivityPowerRepository::class),
    ActivityHeartRateRepository::class => DI\autowire(StreamBasedActivityHeartRateRepository::class),
    ImageRepository::class => DI\autowire(ActivityBasedImageRepository::class),
    ChallengeRepository::class => DI\autowire(DbalChallengeRepository::class),
    FtpRepository::class => DI\autowire(DbalFtpRepository::class),
    GearRepository::class => DI\autowire(DbalGearRepository::class),
    // Twig Environment.
    FilesystemLoader::class => DI\create(FilesystemLoader::class)->constructor($appRoot.'/templates'),
    TwigEnvironment::class => DI\factory([TwigBuilder::class, 'build']),
    // Doctrine Dbal.
    Connection::class => function (Settings $settings): Connection {
        return DriverManager::getConnection($settings->get('doctrine.connection'));
    },
    // Doctrine EntityManager.
    EntityManager::class => function (Settings $settings): EntityManager {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            $settings->get('doctrine.metadata_dirs'),
            $settings->get('doctrine.dev_mode'),
        );

        $connection = DriverManager::getConnection($settings->get('doctrine.connection'), $config);

        return new EntityManager($connection, $config);
    },
    EntityManagerInterface::class => DI\get(EntityManager::class),
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
];

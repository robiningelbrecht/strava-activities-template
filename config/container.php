<?php

use App\Domain\Strava\Activity\StravaActivityRepository;
use App\Domain\Strava\Activity\Stream\StravaActivityStreamRepository;
use App\Domain\Strava\Challenge\StravaChallengeRepository;
use App\Domain\Strava\Gear\StravaGearRepository;
use App\Domain\Strava\StravaClientId;
use App\Domain\Strava\StravaClientSecret;
use App\Domain\Strava\StravaRefreshToken;
use App\Infrastructure\Console\ConsoleCommandContainer;
use App\Infrastructure\Environment\Environment;
use App\Infrastructure\Environment\Settings;
use App\Infrastructure\Twig\TwigBuilder;
use Dotenv\Dotenv;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use SleekDB\Store;
use Symfony\Component\Console\Application;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

$appRoot = Settings::getAppRoot();

$dotenv = Dotenv::createImmutable($appRoot);
$dotenv->load();

return [
    // Clock.
    Clock::class => DI\factory([SystemClock::class, 'fromSystemTimezone']),
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
    StravaActivityRepository::class => DI\autowire()->constructorParameter('store', new Store('activities', $appRoot.'/database', [
        'auto_cache' => false,
        'timeout' => false,
    ])),
    StravaChallengeRepository::class => DI\autowire()->constructorParameter('store', new Store('challenges', $appRoot.'/database', [
        'auto_cache' => false,
        'timeout' => false,
    ])),
    StravaGearRepository::class => DI\autowire()->constructorParameter('store', new Store('gears', $appRoot.'/database', [
        'auto_cache' => false,
        'timeout' => false,
    ])),
    StravaActivityStreamRepository::class => DI\autowire()->constructorParameter('store', new Store('activity-streams', $appRoot.'/database', [
        'auto_cache' => false,
        'timeout' => false,
    ])),
    // File system.
    Filesystem::class => DI\autowire()->constructorParameter('adapter', new LocalFilesystemAdapter(
        Settings::getAppRoot()
    )),
];
